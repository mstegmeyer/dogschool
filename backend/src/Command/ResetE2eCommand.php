<?php

declare(strict_types=1);

namespace App\Command;

use App\DataFixtures\CourseTypeFixtures;
use App\DataFixtures\TrainerFixtures;
use App\E2e\E2eSeedService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpKernel\KernelInterface;

#[AsCommand(
    name: 'app:e2e:reset',
    description: 'Reset the dedicated E2E database, seed deterministic data, and write the Playwright manifest.',
)]
final class ResetE2eCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly KernelInterface $kernel,
        private readonly CourseTypeFixtures $courseTypeFixtures,
        private readonly TrainerFixtures $trainerFixtures,
        private readonly E2eSeedService $seedService,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ($this->kernel->getEnvironment() !== 'e2e') {
            $io->error('app:e2e:reset must be run with APP_ENV=e2e.');

            return Command::FAILURE;
        }

        $this->ensureJwtKeys();
        $this->resetSchema();

        $this->courseTypeFixtures->load($this->em);
        $this->trainerFixtures->load($this->em);

        $manifest = $this->seedService->seed();
        $manifestPath = $this->writeManifest($manifest);

        $io->success(sprintf('E2E environment reset. Manifest written to %s', $manifestPath));

        return Command::SUCCESS;
    }

    private function ensureJwtKeys(): void
    {
        $privateKeyPath = $this->resolveConfiguredPath('JWT_SECRET_KEY', 'var/jwt_e2e/private.pem');
        $publicKeyPath = $this->resolveConfiguredPath('JWT_PUBLIC_KEY', 'var/jwt_e2e/public.pem');
        $configDir = dirname($privateKeyPath);

        if (!is_dir($configDir) && !mkdir($configDir, 0777, true) && !is_dir($configDir)) {
            throw new \RuntimeException(sprintf('Could not create JWT directory at %s', $configDir));
        }

        $passphrase = $this->readEnvVar('JWT_PASSPHRASE') ?? 'change-me';
        if ($this->jwtKeysAreUsable($privateKeyPath, $publicKeyPath, $passphrase)) {
            return;
        }

        $resource = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        if ($resource === false) {
            throw new \RuntimeException('Unable to generate JWT private key resource.');
        }

        $privateKey = '';
        if (!openssl_pkey_export($resource, $privateKey, $passphrase)) {
            throw new \RuntimeException('Unable to export JWT private key.');
        }

        $details = openssl_pkey_get_details($resource);
        if ($details === false || !isset($details['key']) || !is_string($details['key'])) {
            throw new \RuntimeException('Unable to derive JWT public key.');
        }

        file_put_contents($privateKeyPath, $privateKey);
        file_put_contents($publicKeyPath, $details['key']);
    }

    private function jwtKeysAreUsable(string $privateKeyPath, string $publicKeyPath, string $passphrase): bool
    {
        if (!is_file($privateKeyPath) || !is_file($publicKeyPath)) {
            return false;
        }

        $privateKey = file_get_contents($privateKeyPath);
        $publicKey = file_get_contents($publicKeyPath);
        if (!is_string($privateKey) || !is_string($publicKey)) {
            return false;
        }

        $resource = openssl_pkey_get_private($privateKey, $passphrase);
        if ($resource === false) {
            return false;
        }

        $details = openssl_pkey_get_details($resource);
        if ($details === false || !isset($details['key']) || !is_string($details['key'])) {
            return false;
        }

        return trim($details['key']) === trim($publicKey);
    }

    private function resetSchema(): void
    {
        $connection = $this->em->getConnection();
        $params = $connection->getParams();
        $path = $params['path'] ?? null;

        if ($connection->isConnected()) {
            $connection->close();
        }

        if (is_string($path) && $path !== '') {
            $directory = dirname($path);
            if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
                throw new \RuntimeException(sprintf('Could not create SQLite directory at %s', $directory));
            }
        }

        if (is_string($path) && $path !== '' && is_file($path)) {
            unlink($path);
        }

        $metadata = $this->em->getMetadataFactory()->getAllMetadata();
        $schemaTool = new SchemaTool($this->em);
        $schemaTool->createSchema($metadata);
    }

    /**
     * @param array<string, mixed> $manifest
     */
    private function writeManifest(array $manifest): string
    {
        $rootDir = dirname($this->kernel->getProjectDir());
        $cacheDir = $rootDir.'/tests/.cache';
        if (!is_dir($cacheDir) && !mkdir($cacheDir, 0777, true) && !is_dir($cacheDir)) {
            throw new \RuntimeException(sprintf('Could not create manifest directory at %s', $cacheDir));
        }

        $manifestPath = $cacheDir.'/e2e-manifest.json';
        file_put_contents($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);

        return $manifestPath;
    }

    private function resolveConfiguredPath(string $name, string $fallbackRelativePath): string
    {
        $value = $this->readEnvVar($name);
        if ($value === null || $value === '') {
            return $this->kernel->getProjectDir().'/'.$fallbackRelativePath;
        }

        $resolved = str_replace(
            ['%kernel.project_dir%', '%kernel.environment%'],
            [$this->kernel->getProjectDir(), $this->kernel->getEnvironment()],
            $value,
        );

        if (str_starts_with($resolved, 'file://')) {
            return substr($resolved, 7);
        }

        return $resolved;
    }

    private function readEnvVar(string $name): ?string
    {
        $value = $_SERVER[$name] ?? $_ENV[$name] ?? getenv($name);

        return is_string($value) && $value !== '' ? $value : null;
    }
}

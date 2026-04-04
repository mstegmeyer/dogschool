import { expect, type Page } from '@playwright/test';

export class AuthPage {
    constructor(private readonly page: Page) {}

    async gotoLogin(): Promise<void> {
        await this.page.goto('/login');
        await expect(this.page.getByRole('heading', { name: 'Anmelden' })).toBeVisible();
    }

    async gotoRegister(): Promise<void> {
        await this.page.goto('/register');
        await expect(this.page.getByRole('heading', { name: 'Registrieren' })).toBeVisible();
    }

    async loginCustomer(email: string, password: string): Promise<void> {
        await this.gotoLogin();
        const customerForm = this.page.locator('form').filter({ has: this.page.getByPlaceholder('name@beispiel.de') });
        await customerForm.getByPlaceholder('name@beispiel.de').fill(email);
        await customerForm.getByPlaceholder('••••••••').fill(password);
        await this.page.getByRole('button', { name: 'Anmelden' }).click();
    }

    async loginAdmin(username: string, password: string): Promise<void> {
        await this.gotoLogin();
        await this.page.getByRole('tab', { name: 'Trainer' }).click();
        const adminForm = this.page.locator('form').filter({ has: this.page.getByPlaceholder('florian') });
        await adminForm.getByPlaceholder('florian').fill(username);
        await adminForm.getByPlaceholder('••••••••').fill(password);
        await this.page.getByRole('button', { name: 'Anmelden' }).click();
    }

    async register(name: string, email: string, password: string): Promise<void> {
        await this.gotoRegister();
        await this.page.getByLabel('Name').fill(name);
        await this.page.getByLabel('E-Mail').fill(email);
        await this.page.getByPlaceholder('Mindestens 6 Zeichen').fill(password);
        await this.page.getByPlaceholder('Passwort wiederholen').fill(password);
        await this.page.getByRole('button', { name: 'Konto erstellen' }).click();
    }
}

import { promises as fs } from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

export type CustomerPersona =
  | 'customer_empty'
  | 'customer_single_dog'
  | 'customer_multi_dog'
  | 'customer_dashboard'
  | 'customer_calendar_multi'
  | 'customer_calendar_booked'
  | 'customer_profile'
  | 'customer_contracts'
  | 'customer_hotel_booking'
  | 'customer_contract_pending'
  | 'customer_contract_approve'
  | 'customer_contract_decline'
  | 'customer_contract_cancel'
  | 'customer_archive_booking'
  | 'customer_calendar_cancel'
  | 'customer_fill_01'
  | 'customer_fill_02'
  | 'customer_fill_03'
  | 'customer_fill_04'
  | 'customer_fill_05'
  | 'customer_fill_06'
  | 'customer_fill_07'
  | 'customer_fill_08'
  | 'customer_fill_09'
  | 'customer_fill_10'
  | 'customer_fill_11'
  | 'customer_fill_12'
  | 'customer_fill_13'
  | 'customer_fill_14'
  | 'customer_fill_15';

export interface SeedCustomer {
    id: string,
    name: string,
    email: string,
    password: string,
    dogIds: string[],
    dogNames: string[],
    dogShoulderHeights: number[],
}

export interface SeedRoom {
    id: string,
    name: string,
    squareMeters: number,
}

export interface E2eManifest {
    fixedNow: string,
    week: {
        monday: string,
        nextMonday: string,
    },
    admin: {
        username: string,
        password: string,
    },
    customers: Record<CustomerPersona, SeedCustomer>,
    trainers: Record<string, { id: string; username: string; fullName: string }>,
    courseTypes: Record<string, { id: string; code: string; name: string }>,
    courses: Record<string, string>,
    courseDates: Record<string, { current: string; next: string }>,
    contracts: Record<string, string>,
    notifications: Record<string, string>,
    hotelRooms: Record<string, SeedRoom>,
    hotelBookings: Record<string, string>,
}

const FIXTURE_ROOT = path.dirname(fileURLToPath(import.meta.url));
const TESTS_ROOT = path.resolve(FIXTURE_ROOT, '../..');
const MANIFEST_PATH = path.join(TESTS_ROOT, '.cache', 'e2e-manifest.json');

export async function readManifest(): Promise<E2eManifest> {
    const payload = await fs.readFile(MANIFEST_PATH, 'utf8');
    return JSON.parse(payload) as E2eManifest;
}

export function manifestPath(): string {
    return MANIFEST_PATH;
}

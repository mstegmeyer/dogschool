// ---------------------------------------------------------------------------
// Generic API response wrappers
// ---------------------------------------------------------------------------

export interface ApiPagination {
    page: number,
    limit: number,
    total: number,
    pages: number,
}

export interface ApiListResponse<T> {
    items: T[],
    pagination?: ApiPagination,
}

// ---------------------------------------------------------------------------
// Scalar domain types
// ---------------------------------------------------------------------------

export type DayOfWeek = 1 | 2 | 3 | 4 | 5 | 6 | 7;
export type CourseLevel = 0 | 1 | 2 | 3 | 4;
export type ContractState = 'REQUESTED' | 'ACTIVE' | 'DECLINED' | 'CANCELLED';
export type ContractType = 'PERPETUAL';
export type CreditTransactionType = 'WEEKLY_GRANT' | 'BOOKING' | 'CANCELLATION' | 'MANUAL_ADJUSTMENT';
export type HotelBookingState = 'REQUESTED' | 'CONFIRMED' | 'DECLINED';
export type RecurrenceKind = 'RECURRING' | 'ONE_TIME' | 'DROP_IN';
export type DogGender = 'male' | 'female';
export type AuthRole = 'admin' | 'customer';

// ---------------------------------------------------------------------------
// Value objects
// ---------------------------------------------------------------------------

export interface Address {
    street: string | null,
    postalCode: string | null,
    city: string | null,
    country: string | null,
}

export interface BankAccount {
    iban: string | null,
    bic: string | null,
    accountHolder: string | null,
}

// ---------------------------------------------------------------------------
// Domain entities
// ---------------------------------------------------------------------------

export interface Customer {
    id: string,
    name: string,
    email: string,
    createdAt: string,
    address: Address,
    bankAccount: BankAccount,
}

export interface Dog {
    id: string,
    name: string,
    color: string | null,
    gender: DogGender | null,
    race: string | null,
    shoulderHeightCm: number,
}

export interface Room {
    id: string,
    name: string,
    squareMeters: number,
    createdAt: string,
}

export interface RoomOccupancySegment {
    startAt: string,
    endAt: string,
    usedSquareMeters: number,
    freeSquareMeters: number,
    bookingCount: number,
    dogNames: string[],
}

export interface RoomAvailability {
    roomId: string,
    roomName: string,
    squareMeters: number,
    available: boolean,
    requiredSquareMeters: number,
    peakRequiredSquareMeters: number,
    remainingSquareMeters: number,
    segments: RoomOccupancySegment[],
}

export interface HotelBooking {
    id: string,
    customerId: string | null,
    customerName: string | null,
    dogId: string | null,
    dogName: string | null,
    dogShoulderHeightCm: number | null,
    roomId: string | null,
    roomName: string | null,
    startAt: string,
    endAt: string,
    state: HotelBookingState,
    createdAt: string,
    availableRooms?: RoomAvailability[],
}

export interface HotelOccupancyRoom {
    room: Room,
    peakRequiredSquareMeters: number,
    segments: RoomOccupancySegment[],
    bookings: HotelBooking[],
}

export interface CourseTypeInfo {
    code: string,
    name: string,
    recurrenceKind: RecurrenceKind,
}

export interface CourseType {
    id: string,
    code: string,
    name: string,
    recurrenceKind: RecurrenceKind,
}

export interface TrainerInfo {
    id: string,
    username: string,
    fullName: string,
    phone: string | null,
}

export interface Course {
    id: string,
    dayOfWeek: DayOfWeek,
    startTime: string,
    endTime: string,
    durationMinutes: number | null,
    type: CourseTypeInfo | null,
    level: CourseLevel,
    trainer: TrainerInfo | null,
    comment: string | null,
    archived: boolean,
    subscriberCount: number,
    subscribers: { id: string; name: string }[],
}

export interface CourseDateBooking {
    id: string,
    dogId: string | null,
    dogName?: string | null,
    customerId?: string | null,
    customerName?: string | null,
}

export interface CourseDate {
    id: string,
    courseId: string,
    courseType: CourseTypeInfo | null,
    level: CourseLevel | null,
    date: string,
    dayOfWeek: DayOfWeek,
    startTime: string,
    endTime: string,
    trainer: TrainerInfo | null,
    courseTrainer?: TrainerInfo | null,
    trainerOverridden?: boolean,
    cancelled: boolean,
    bookingCount: number,
    createdAt: string,
    booked?: boolean,
    bookings?: CourseDateBooking[],
    /** Number of customers subscribed to the underlying course series (admin only). */
    subscriberCount?: number,
    /** Subscribed customers with id + name (admin only). */
    subscribers?: { id: string; name: string }[],
    /** Customer is subscribed to the underlying course (recurring series). */
    subscribed?: boolean,
    /** Start + 24h passed — no book/cancel (matches backend). */
    bookingWindowClosed?: boolean,
}

export interface NextWeeklyGrantHint {
    contractId: string,
    amount: number,
    dogName: string | null,
    nextGrantAt: string,
    currentWeekRef: string,
    pendingGrantThisWeek: boolean,
}

export interface CreditTransaction {
    id: string,
    amount: number,
    type: CreditTransactionType,
    description: string,
    courseDateId: string | null,
    contractId: string | null,
    weekRef: string | null,
    createdAt: string,
}

export interface Contract {
    id: string,
    contractGroupId: string,
    version: number,
    dogId: string | null,
    dogName?: string | null,
    customerId: string | null,
    customerName?: string | null,
    startDate: string | null,
    endDate: string | null,
    price: string,
    /** Same as price for PERPETUAL (monthly). */
    priceMonthly?: string | null,
    type: ContractType,
    coursesPerWeek: number,
    state: ContractState,
    createdAt: string,
}

export interface NotificationCourseRef {
    id: string,
    typeCode: string | null,
    typeName: string | null,
    dayOfWeek: DayOfWeek,
    startTime: string,
    endTime: string,
}

export interface Notification {
    id: string,
    title: string,
    message: string,
    authorName: string | null,
    authorId: string | null,
    isGlobal: boolean,
    courses: NotificationCourseRef[],
    courseIds: string[],
    pinnedUntil: string | null,
    isPinned: boolean,
    createdAt: string,
}

export interface Booking {
    id: string,
    customerId: string,
    dogId: string,
    courseDateId: string,
    courseDate: CourseDate | null,
    active: boolean,
    createdAt: string,
    cancelledAt: string | null,
}

export interface CreditSummary {
    customerId: string,
    balance: number,
    items: CreditTransaction[],
}

// ---------------------------------------------------------------------------
// API request / response shapes used across multiple pages
// ---------------------------------------------------------------------------

export interface CustomerCreditsResponse {
    balance: number,
    nextWeeklyGrants?: NextWeeklyGrantHint[],
    items: CreditTransaction[],
}

export interface BookingResponse {
    creditBalance: number,
}

export interface HotelOccupancyResponse {
    from: string,
    to: string,
    items: HotelOccupancyRoom[],
}

export interface HotelMovementsResponse {
    from: string,
    to: string,
    arrivals: HotelBooking[],
    departures: HotelBooking[],
}

export interface CalendarSubscriptionResponse {
    path: string,
}

export interface CustomerCourseDetailResponse {
    course: Course,
    upcomingDates: CourseDate[],
    notifications: Notification[],
}

export interface ProfileUpdatePayload {
    name?: string,
    email?: string,
    password?: string,
    address: Pick<Address, 'street' | 'postalCode' | 'city'>,
    bankAccount: Pick<BankAccount, 'iban' | 'bic' | 'accountHolder'>,
}

export interface NavLink {
    label: string,
    icon: string,
    to: string,
}

import { LucideIcon } from 'lucide-react';

export type UserRole = 'perawat' | 'dokter_operator' | 'dokter_anestesi' | 'kepala_ruangan' | 'admin';

export type NurseType = 'sirkuler' | 'instrumen' | 'anestesi';

export interface User {
    id: number;
    name: string;
    username: string;
    email: string | null;
    avatar?: string;
    role: UserRole;
    role_label: string;
    nurse_type: NurseType | null;
    nurse_type_label: string | null;
    nip: string | null;
    sip: string | null;
    is_active: boolean;
}

export interface Auth {
    user: User | null;
}

export interface FlashMessages {
    success?: string | null;
    error?: string | null;
    warning?: string | null;
    info?: string | null;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavGroup {
    title: string;
    items: NavItem[];
}

export interface NavItem {
    title: string;
    url: string;
    icon?: LucideIcon | null;
    isActive?: boolean;
}

export interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

export interface PaginatedData<T> {
    data: T[];
    links: PaginationLink[];
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
}

export interface AdminStats {
    users_total: number;
    users_active: number;
    users_inactive: number;
    rooms_active: number;
    instruments_active: number;
    surgery_types_active: number;
    anesthesia_types_active: number;
    medical_diagnoses_active: number;
}

export interface SharedData {
    name: string;
    auth: Auth;
    flash: FlashMessages;
    sessionLifetime: number;
    [key: string]: unknown;
}

export type Role = 'worker' | 'admin' | 'super_admin';

export type User = {
    id: number;
    organisation_id: number | null;
    role: Role;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
};

export type Auth = {
    user: User;
    /** The signed-in user's landing page: the platform for the super admin, the club dashboard for everyone else. */
    home: string;
};

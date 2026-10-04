export type Status = 'missing' | 'expired' | 'expiring' | 'valid';

export type CertificateRow = {
    id: number;
    type: string;
    reference: string | null;
    issued_on: string;
    expires_on: string;
    status: Status;
};

export const statusLabel: Record<Status, string> = {
    missing: 'No certificates',
    expired: 'Expired',
    expiring: 'Expiring soon',
    valid: 'Valid',
};

export const statusVariant: Record<
    Status,
    'destructive' | 'outline' | 'secondary'
> = {
    missing: 'destructive',
    expired: 'destructive',
    expiring: 'outline',
    valid: 'secondary',
};

export const longDate = new Intl.DateTimeFormat('en-GB', {
    dateStyle: 'medium',
});

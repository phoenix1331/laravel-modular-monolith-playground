export type Status = 'available' | 'maybe' | 'unavailable';

export type FixtureSummary = {
    id: number;
    team: string;
    opponent: string;
    kick_off_at: string;
    is_home: boolean;
    venue: string | null;
};

export const statuses: { value: Status; label: string }[] = [
    { value: 'available', label: 'Available' },
    { value: 'maybe', label: 'Maybe' },
    { value: 'unavailable', label: 'Unavailable' },
];

export const kickOff = new Intl.DateTimeFormat('en-GB', {
    weekday: 'short',
    day: 'numeric',
    month: 'short',
    hour: '2-digit',
    minute: '2-digit',
});

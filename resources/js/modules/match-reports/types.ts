export type FixtureSummary = {
    id: number;
    team: string;
    opponent: string;
    kick_off_at: string;
    is_home: boolean;
    competition: string | null;
};

export type Result = 'won' | 'drew' | 'lost';

export const matchDate = new Intl.DateTimeFormat('en-GB', {
    weekday: 'short',
    day: 'numeric',
    month: 'short',
});

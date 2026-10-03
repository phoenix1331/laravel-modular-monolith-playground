/**
 * Internal to the fixtures module. Other modules read fixtures through the
 * backend FixtureLookup contract, not these page types.
 */
export type FixtureRow = {
    id: number;
    team: string;
    opponent: string;
    kick_off_at: string;
    is_home: boolean;
    venue: string | null;
    competition: string | null;
    can_update: boolean;
};

export type Option = { id: number; name: string };

export type Venue = Option & {
    address: string | null;
    postcode: string | null;
};

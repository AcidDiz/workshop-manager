export type AdminNextUpcomingWorkshop = {
    id: number;
    title: string;
    starts_at: string;
    ends_at: string;
    confirmed_registrations_count: number;
    capacity: number;
};

export type AdminWorkshopStatistics = {
    workshops: {
        total: number;
        upcoming: number;
        closed: number;
    };
    registrations: {
        confirmed: number;
        waiting_list: number;
        total: number;
    };
    next_upcoming_workshop: AdminNextUpcomingWorkshop | null;
    generated_at: string;
};

export type WorkshopCategoryRef = {
    id: number | null;
    name: string;
};

export type WorkshopCreatorRef = {
    id: number | null;
    name: string;
};

/** Row shape for workshop list (Inertia `workshopList` prop). */
export type WorkshopListItem = {
    id: number;
    title: string;
    description: string | null;
    starts_at: string;
    ends_at: string;
    capacity: number;
    confirmed_registrations_count: number;
    enrollment: string;
    category: WorkshopCategoryRef;
    creator: WorkshopCreatorRef;
    timing_status: 'upcoming' | 'closed';
    /** Tailwind classes from `WorkshopStatusEnum::badgeClassName()` (server). */
    timing_status_badge_class: string;
    /** Current user's registration for this workshop, if any. */
    my_registration_status: 'confirmed' | 'waiting_list' | null;
};

/** @deprecated Use WorkshopListItem; kept for gradual renames. */
export type WorkshopSummary = WorkshopListItem;

export type WorkshopPermissions = {
    view: boolean;
    manage: boolean;
};

/** Category row for admin workshop forms (Inertia `categories` prop). */
export type WorkshopCategoryOption = {
    id: number;
    name: string;
};

/** Payload for editing a workshop (`WorkshopFormResource`). */
export type WorkshopFormPayload = {
    id: number;
    title: string;
    description: string | null;
    workshop_category_id: number | null;
    starts_at: string;
    ends_at: string;
    capacity: number;
};

/** Admin workshop detail page (`WorkshopShowResource` → Inertia `workshop`). */
export type WorkshopShowPayload = {
    id: number;
    title: string;
    description: string | null;
    starts_at: string;
    ends_at: string;
    capacity: number;
    confirmed_registrations_count: number;
    waiting_list_registrations_count: number;
    enrollment: string;
    category: WorkshopCategoryRef;
    creator: WorkshopCreatorRef;
};

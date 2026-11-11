export type CommunityAuthor = {
    name: string;
    is_team: boolean;
    avatar?: string | null;
};

export type CommunityChannelSummary = {
    id: number;
    name: string;
    slug: string;
    emoji: string | null;
    staff_only: boolean;
};

export type CommunityChannel = CommunityChannelSummary & {
    description: string | null;
    messages_count: number;
    can_post: boolean;
};

export type CommunityMessage = {
    id: number;
    body: string | null;
    body_html: string;
    images: { id: number; url: string }[];
    author: CommunityAuthor;
    created_at: string | null;
    edited: boolean;
    reply_to: { id: number; author: string; excerpt: string } | null;
    reactions: { emoji: string; count: number; mine: boolean }[];
    can: { update: boolean; delete: boolean; report: boolean };
};

export type LessonDiscussion = {
    id: number;
    excerpt: string;
    author: CommunityAuthor;
    replies_count: number;
    created_at: string | null;
    course: { slug: string; title: string };
    lesson: { slug: string; title: string };
};

export type BellNotification = {
    id: string;
    actor: string | null;
    message: string;
    excerpt: string | null;
    read: boolean;
    created_at: string | null;
};

export type AdminCommunitySpace = {
    id: number;
    name: string;
    slug: string;
    emoji: string | null;
    description: string | null;
    staff_only: boolean;
    position: number;
    messages_count: number;
};

export type AdminCommunityReport = {
    id: number;
    reason: string | null;
    reporter: string;
    created_at: string | null;
    content: {
        id: number;
        body: string;
        author: string;
        deleted: boolean;
        channel: { name: string; slug: string } | null;
    } | null;
};

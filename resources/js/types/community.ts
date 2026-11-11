export type CommunityAuthor = {
    name: string;
    is_team: boolean;
};

export type CommunitySpace = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    topics_count: number;
    can_post: boolean;
};

export type CommunityTopicSummary = {
    id: number;
    title: string;
    excerpt: string;
    space: { name: string; slug: string };
    author: CommunityAuthor;
    replies_count: number;
    solved: boolean;
    pinned: boolean;
    created_at: string | null;
    last_activity_at: string;
};

export type CommunityTopicFilter = 'recentes' | 'sem-resposta' | 'meus';

export type CommunityTopic = {
    id: number;
    title: string;
    body: string | null;
    body_html: string;
    space: { id: number; name: string; slug: string };
    author: CommunityAuthor;
    pinned: boolean;
    edited: boolean;
    created_at: string | null;
    can: {
        update: boolean;
        delete: boolean;
        solve: boolean;
        moderate: boolean;
        report: boolean;
    };
};

export type CommunityReply = {
    id: number;
    body: string | null;
    body_html: string;
    author: CommunityAuthor;
    created_at: string | null;
    edited: boolean;
    is_solution: boolean;
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
    description: string | null;
    staff_only: boolean;
    position: number;
    topics_count: number;
};

export type AdminCommunityReport = {
    id: number;
    reason: string | null;
    reporter: string;
    created_at: string | null;
    content: {
        kind: 'topic' | 'reply';
        body: string;
        author: string;
        deleted: boolean;
        topic: { id: number; title: string } | null;
    } | null;
};

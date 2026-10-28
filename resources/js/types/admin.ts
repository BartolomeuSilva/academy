export type PublicationStatus = 'draft' | 'published';

export type AttachmentKind = 'image' | 'pdf';

export type AdminCourseListItem = {
    id: number;
    title: string;
    summary: string;
    status: PublicationStatus;
    cover_url: string | null;
    modules_count: number;
    lessons_count: number;
};

export type AdminCourse = {
    id: number;
    title: string;
    slug: string;
    summary: string;
    description: string | null;
    status: PublicationStatus;
    cover_url: string | null;
};

export type AdminLessonListItem = {
    id: number;
    title: string;
    status: PublicationStatus;
    attachments_count: number;
};

export type AdminModule = {
    id: number;
    title: string;
    lessons: AdminLessonListItem[];
};

export type AdminLesson = {
    id: number;
    title: string;
    slug: string;
    description: string | null;
    status: PublicationStatus;
    module: { id: number; title: string };
    course: { id: number; slug: string; title: string };
};

export type AdminAttachment = {
    id: number;
    kind: AttachmentKind;
    original_name: string;
    size: number;
    url: string;
};

export type VideoStatus =
    | 'uploading'
    | 'queued'
    | 'processing'
    | 'ready'
    | 'failed';

export type AdminLessonVideo = {
    id: number;
    original_name: string;
    duration_seconds: number | null;
    renditions: string[];
    poster_url: string | null;
    playlist_url: string;
    processed_at: string | null;
};

export type AdminPendingVideo = {
    id: number;
    status: VideoStatus;
    original_name: string;
    error_message: string | null;
};

export type AdminComment = {
    id: number;
    body: string;
    author: string;
    is_reply: boolean;
    edited: boolean;
    created_at: string | null;
    lesson: { slug: string; title: string };
    course: { slug: string; title: string };
};

export type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
};

import type { AttachmentKind, PublicationStatus } from './admin';

export type CatalogCourse = {
    id: number;
    slug: string;
    title: string;
    summary: string;
    cover_url: string | null;
    lessons_count: number;
    completed_lessons_count: number;
    duration_seconds: number;
    started: boolean;
};

export type OutlineLesson = {
    id: number;
    slug: string;
    title: string;
    duration_seconds: number | null;
    completed: boolean;
};

export type OutlineModule = {
    id: number;
    title: string;
    lessons: OutlineLesson[];
};

export type LessonCourse = {
    id: number;
    slug: string;
    title: string;
    summary: string;
    description_html: string | null;
};

export type LessonDetails = {
    id: number;
    slug: string;
    title: string;
    description_html: string | null;
    status: PublicationStatus;
};

export type LessonVideo = {
    playlist_url: string;
    poster_url: string | null;
    duration_seconds: number | null;
};

export type LessonProgressState = {
    completed: boolean;
    start_at_seconds: number;
};

export type LessonMaterial = {
    id: number;
    kind: AttachmentKind;
    original_name: string;
    size: number;
    url: string;
    download_url: string;
};

export type LessonComment = {
    id: number;
    body: string | null;
    author: string | null;
    created_at: string | null;
    edited: boolean;
    deleted: boolean;
    can_update: boolean;
    can_delete: boolean;
};

export type LessonRootComment = LessonComment & {
    replies: LessonComment[];
};

export type LessonComments = {
    data: LessonRootComment[];
    has_more: boolean;
    total: number;
};

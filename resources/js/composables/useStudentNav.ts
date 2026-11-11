import { BookOpen, FolderOpen, MessagesSquare } from '@lucide/vue';
import { index as community } from '@/routes/community';
import { index as catalog } from '@/routes/courses';
import { index as materials } from '@/routes/materials';
import type { NavItem } from '@/types';

/**
 * Sections every student reaches from the sidebar and, on phones, from the bottom bar.
 */
export function useStudentNav(): NavItem[] {
    return [
        {
            title: 'Cursos',
            href: catalog().url,
            icon: BookOpen,
        },
        {
            title: 'Materiais',
            href: materials().url,
            icon: FolderOpen,
        },
        {
            title: 'Comunidade',
            href: community().url,
            icon: MessagesSquare,
        },
    ];
}

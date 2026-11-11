import {
    BookOpen,
    FolderOpen,
    House,
    MessagesSquare,
    Trophy,
} from '@lucide/vue';
import { index as achievements } from '@/routes/achievements';
import { index as community } from '@/routes/community';
import { index as home, library } from '@/routes/courses';
import { index as materials } from '@/routes/materials';
import type { NavItem } from '@/types';

export type StudentNavItem = NavItem & {
    /** Label for the narrow phone bottom bar. */
    shortTitle?: string;
    /** Whether the phone bottom bar shows it; the rest stay in the menu. */
    inBottomBar: boolean;
};

/**
 * Sections every student reaches from the sidebar and, on phones, from the bottom bar.
 */
export function useStudentNav(): StudentNavItem[] {
    return [
        {
            title: 'Início',
            href: home().url,
            icon: House,
            inBottomBar: true,
        },
        {
            title: 'Meus cursos',
            href: library().url,
            icon: BookOpen,
            shortTitle: 'Cursos',
            inBottomBar: true,
        },
        {
            title: 'Materiais',
            href: materials().url,
            icon: FolderOpen,
            inBottomBar: false,
        },
        {
            title: 'Comunidade',
            href: community().url,
            icon: MessagesSquare,
            inBottomBar: true,
        },
        {
            title: 'Conquistas',
            href: achievements().url,
            icon: Trophy,
            inBottomBar: true,
        },
    ];
}

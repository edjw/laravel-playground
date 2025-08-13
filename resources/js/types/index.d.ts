import type { LucideIcon } from 'lucide-vue-next';
import type { Config } from 'ziggy-js';

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavItem {
    title: string;
    href: string;
    icon?: LucideIcon;
    isActive?: boolean;
}

export type AppPageProps<T extends Record<string, unknown> = Record<string, unknown>> = T & {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    ziggy: Config & { location: string };
    sidebarOpen: boolean;
};

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
}

export interface PlaygroundTool {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    icon: string;
    component_name: string;
    configuration: Record<string, any> | null;
    saved_data: Record<string, any> | null;
    is_active: boolean;
    user_id: number | null;
    created_at: string;
    updated_at: string;
}

export interface WordCounterStats {
    words: number;
    characters: number;
    characters_no_spaces: number;
    lines: number;
    paragraphs: number;
}

export interface JsonFormatterResult {
    formatted?: string;
    minified?: string;
    valid: boolean;
    size_original?: number;
    size_formatted?: number;
    size_minified?: number;
    error?: string;
}

export interface ColorPaletteResult {
    palette: {
        primary: string;
        lighter: string;
        darker: string;
        complement: string;
        triad1: string;
        triad2: string;
    };
}

export type BreadcrumbItemType = BreadcrumbItem;

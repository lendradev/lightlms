export type CourseSummary = {
    id: number;
    title: string;
    slug: string;
    summary: string;
    instructor_name: string | null;
    level: string;
    duration_minutes: number;
    modules_count?: number;
};

export type Lesson = {
    id: number;
    module_id: number;
    title: string;
    type: 'reading' | 'video' | 'quiz' | 'assignment' | 'forum';
    position: number;
    duration_minutes: number;
    body: string | null;
    is_preview: boolean;
    grade_max?: number;
    due_at?: string | null;
};

export type Module = {
    id: number;
    course_id: number;
    title: string;
    summary: string | null;
    position: number;
    lessons: Lesson[];
};

export type Course = {
    id: number;
    title: string;
    slug: string;
    summary: string;
    description: string | null;
    instructor_name: string | null;
    level: string;
    duration_minutes: number;
    thumbnail_path: string | null;
    published_at: string | null;
    status?: string;
    modules: Module[];
};

export type Paginated<T> = {
    current_page: number;
    data: T[];
    first_page_url: string | null;
    from: number | null;
    last_page: number;
    last_page_url: string | null;
    links: {
        url: string | null;
        label: string;
        active: boolean;
    }[];
    next_page_url: string | null;
    path: string;
    per_page: number;
    prev_page_url: string | null;
    to: number | null;
    total: number;
};

export type AuthUser = {
    id: number;
    name: string;
    email: string;
    role: 'student' | 'instructor' | 'admin';
};

export type SharedProps = {
    app: {
        name: string;
        locale: string;
        availableLocales: string[];
        registrationMode: string;
        installed: boolean;
    };
    auth: {
        user: AuthUser | null;
    };
    flash: {
        success: string | null;
        error: string | null;
    };
};

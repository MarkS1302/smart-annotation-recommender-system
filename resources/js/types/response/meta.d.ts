import {Link} from "@/types/response/link";

export interface Meta {
    current_page: number;
    from: number;
    last_page: number;
    links: Link[];
    path: string;
    per_page: number;
    to: number;
    total: number;
    next_cursor?: string;
    prev_cursor?: string;
}
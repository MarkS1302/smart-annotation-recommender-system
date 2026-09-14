import {Meta} from "@/types/response/meta";
import {Links} from "@/types/response/links";

export interface ResourceCollection<T> {
    data: T[];
    meta: Meta,
    links: Links
}
import type { Permission } from '@/types/models/permission';

export interface Role {
    id: number;
    name: string;
    permissions: Permission[];
}

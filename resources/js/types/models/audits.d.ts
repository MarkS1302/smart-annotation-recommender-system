export interface Audit extends Model {
    id: number;
    event: string;
    auditable_type: string;
    auditable_id: number | string;
    old_values: Record<string, unknown>;
    new_values: Record<string, unknown>;
    user: User | null;
    ip_address: string | null;
    user_agent: string | null;
    created_at: string;
}

export interface User extends Model {
    name: string;
    email: string;
    email_verified_at: string | null;
    roles: Role[];
}

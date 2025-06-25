import { Message } from "./Message";
import { User } from "./User";

export interface Chat {
    id?: number;
    title: string;
    user_id?: number;
    user?: User;
    messages?: Message[];
    created_at?: string;
    updated_at?: string;
}

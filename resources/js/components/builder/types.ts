export interface BOption {
    id: number;
    code: string;
    group: 'addon' | 'platform' | 'creative';
    exclusive_group: string | null;
    name: string;
    description: string | null;
    pricing_mode: 'fixed' | 'per_page' | 'percent_bp';
    amount: number | null;
    max_quantity: number;
}

export interface BType {
    id: number;
    key: string;
    kind: string;
    name: string;
    description: string | null;
    base_price_minor: number | null;
    supports_pages: boolean;
    min_pages: number;
    max_pages: number;
    included_pages: number;
    extra_page_price_minor: number | null;
    options: BOption[];
}

export interface BDiscount {
    code: string;
    name: string;
    description: string | null;
    type: 'fixed' | 'percent_bp';
    value: number;
    condition_type: string;
    condition_value: number | null;
    max_discount_minor: number | null;
}

export interface Currency {
    code: string;
    symbol: string;
    decimals: number;
    position: string;
}

export interface Limits {
    max_files: number;
    max_kb: number;
    mimes: string[];
}

type Names = Record<string, string>;

export interface EstimateLine {
    kind: string;
    code: string;
    name: Names;
    quantity: number;
    unit_price_minor: number | null;
    line_total_minor: number;
    priced: boolean;
}

export interface EstimateDiscount {
    code: string;
    name: Names;
    amount_minor: number;
}

export interface Estimate {
    currency: string;
    pages: number | null;
    lines: EstimateLine[];
    discounts: EstimateDiscount[];
    subtotal_minor: number;
    discount_minor: number;
    total_minor: number;
    has_unpriced_items: boolean;
}

export interface FormState {
    typeId: number | null;
    pages: number | null;
    options: Record<number, number>;
    customer_name: string;
    phone: string;
    email: string;
    country: string;
    preferred_contact: 'whatsapp' | 'phone' | 'email';
    whatsapp_opt_in: boolean;
    description: string;
    goal: string;
    audience: string;
    expected_date: string;
    budget: string;
    files: File[];
    website: string;
}

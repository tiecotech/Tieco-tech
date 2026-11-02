const paths: Record<string, string> = {
    web: 'M5 4h14a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2zM8 21h8M12 17v4',
    code: 'M8 8l-4 4 4 4M16 8l4 4-4 4M13 6l-2 12',
    mobile: 'M9 2h6a2 2 0 0 1 2 2v16a2 2 0 0 1-2 2H9a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2zM11 18h2',
    ads: 'M4 10v4l12 5V5L4 10zM8 15l1 5',
    brand: 'M4 20l4-1 11-11-3-3L5 16l-1 4zM14 7l3 3',
    growth: 'M4 18l5-6 4 3 7-9M15 6h5v5',
    camera: 'M5 7h14a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2zM8 7l1.5-3h5L16 7M12 10a3.5 3.5 0 1 0 0 7 3.5 3.5 0 0 0 0-7z',
};

export function ServiceIcon({ name }: { name: string | null }) {
    return (
        <svg viewBox="0 0 24 24" aria-hidden="true">
            <path d={paths[name ?? ''] ?? paths.code} />
        </svg>
    );
}

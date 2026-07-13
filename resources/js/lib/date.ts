export function formatDate(date: string): string {
    const [year, month, day] = date.slice(0, 10).split('-').map(Number);

    return new Date(Date.UTC(year, month - 1, day)).toLocaleDateString(
        'en-GB',
        {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
            timeZone: 'UTC',
        },
    );
}

export function todayIn(timezone?: string): string {
    return new Date().toLocaleDateString('en-CA', {
        timeZone: timezone,
    });
}

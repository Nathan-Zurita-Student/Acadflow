export function useTimeAgo() {
    function timeAgo(date: string): string {
        const diff = (Date.now() - new Date(date).getTime()) / 1000
        if (diff < 60) return 'agora'
        if (diff < 3600) return `${Math.floor(diff / 60)}m atrás`
        if (diff < 86400) return `${Math.floor(diff / 3600)}h atrás`
        return `${Math.floor(diff / 86400)}d atrás`
    }

    return { timeAgo }
}

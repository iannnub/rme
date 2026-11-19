import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';

export type CaseStatusValue = 'pre_op' | 'siap_operasi' | 'intra_op' | 'pasca_op' | 'serah_terima' | 'selesai';

const STATUS_STYLES: Record<CaseStatusValue, string> = {
    pre_op: 'border-border bg-secondary text-foreground',
    siap_operasi: 'border-primary/30 bg-accent text-foreground',
    intra_op: 'border-primary bg-primary text-primary-foreground',
    pasca_op: 'border-primary/40 bg-secondary text-foreground',
    serah_terima: 'border-primary/30 bg-accent text-foreground',
    selesai: 'border-emerald-700/30 bg-emerald-50 text-emerald-950 dark:border-emerald-400/40 dark:bg-emerald-950/60 dark:text-emerald-100',
};

export function CaseStatusBadge({ status, label }: { status: string; label: string }) {
    const style = STATUS_STYLES[status as CaseStatusValue] ?? '';

    return (
        <Badge variant="outline" className={cn('rounded-lg px-2.5 py-0.5 font-semibold', style)}>
            {label}
        </Badge>
    );
}

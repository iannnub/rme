import { CaseStatusBadge } from '@/components/cases/case-status-badge';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import { type BreadcrumbItem } from '@/types';
import { Link } from '@inertiajs/react';
import { AlertTriangle, Check, Download, Lock } from 'lucide-react';

export type CaseStageItem = {
    key: string;
    label: string;
    href: string;
    completed: boolean;
    active: boolean;
    locked: boolean;
};

export type CaseChecklistItem = {
    label: string;
    met: boolean;
};

export type CaseHeaderData = {
    id: number;
    case_number: string;
    status: string;
    status_label: string;
    status_order: number;
    admitted_at: string;
    scheduled_at: string;
    origin_ward: string;
    origin_class: string | null;
    diagnosis: string;
    surgery_type: string;
    surgery_profile: 'bedah_saraf' | 'bedah_ortopedi' | null;
    surgery_profile_label: string | null;
    urgency_status: string | null;
    urgency_label: string | null;
    igd_visit_number: string | null;
    igd_admitted_at: string | null;
    dpjp_name: string | null;
    postop_diagnosis: string | null;
    surgery_site: string;
    anesthesia_type: string;
    allergy: string | null;
    room: {
        id: number;
        code: string;
        name: string;
    } | null;
    patient: {
        id: number | null;
        rm_number: string;
        name: string;
        birth_date: string;
        age_label: string;
        gender: string;
        gender_label: string;
        identity_number: string | null;
        guardian_name: string | null;
        guardian_relation: string | null;
        guardian_phone: string | null;
    };
    team: {
        surgeon: string | null;
        anesthesiologist: string | null;
        circulating_nurse: string | null;
        scrub_nurse: string | null;
        anesthesia_nurse: string | null;
    };
    stages: CaseStageItem[];
    next_transition: {
        target: string;
        target_label: string;
        checklist: CaseChecklistItem[];
        can_advance: boolean;
    } | null;
};

interface CaseLayoutProps {
    caseHeader: CaseHeaderData;
    activeTab: string;
    children: React.ReactNode;
}

export default function CaseLayout({ caseHeader, activeTab, children }: CaseLayoutProps) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Kasus Operasi', href: '/kasus' },
        { title: caseHeader.case_number, href: route('cases.show', caseHeader.id) },
    ];

    const tabs = [
        { key: 'ringkasan', label: 'Ringkasan', href: route('cases.show', caseHeader.id), locked: false },
        { key: 'pre-op', label: 'Pre-op', href: route('cases.preop', caseHeader.id), locked: false },
        { key: 'persiapan', label: 'Persiapan', href: route('cases.preparation', caseHeader.id), locked: false },
        { key: 'ssc', label: 'SSC', href: route('cases.ssc.sign-in', caseHeader.id), locked: false },
        {
            key: 'intra-op',
            label: 'Intra-op',
            href: route('cases.intraop', caseHeader.id),
            locked: caseHeader.status_order < 2,
        },
        {
            key: 'pasca-op',
            label: 'Pasca-op',
            href: route('cases.postop', caseHeader.id),
            locked: caseHeader.status_order < 3,
        },
        {
            key: 'serah-terima',
            label: 'Serah terima',
            href: route('cases.handover', caseHeader.id),
            locked: caseHeader.status_order < 4,
        },
        { key: 'riwayat', label: 'Riwayat perubahan', href: route('cases.history', caseHeader.id), locked: false },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <div className="flex min-w-0 flex-1 flex-col">
                {/* Floating Liquid Glass Header Kasus + Stepper + Pill Tabs (Sudut Konsentris) */}
                <div className="liquid-glass-header liquid-morph relative z-20 mx-3 mt-3 rounded-3xl md:mx-4 lg:sticky lg:top-20">
                    <div className="space-y-3 p-3.5 md:p-4">
                        <div className="flex flex-col gap-2 lg:flex-row lg:items-center lg:justify-between">
                            <div className="min-w-0 space-y-1">
                                <div className="flex flex-wrap items-center gap-2">
                                    <h1 className="text-foreground text-lg font-bold tracking-tight">{caseHeader.patient.name}</h1>
                                    <Badge variant="outline" className="bg-card rounded-lg font-mono text-xs">
                                        RM: {caseHeader.patient.rm_number}
                                    </Badge>
                                    <span className="text-muted-foreground text-xs font-medium">
                                        {caseHeader.patient.age_label} • {caseHeader.patient.gender_label}
                                    </span>
                                    <CaseStatusBadge status={caseHeader.status} label={caseHeader.status_label} />
                                    {caseHeader.urgency_label && (
                                        <Badge
                                            variant={caseHeader.urgency_status === 'emergency' ? 'destructive' : 'secondary'}
                                            className="rounded-lg"
                                        >
                                            {caseHeader.urgency_label}
                                        </Badge>
                                    )}
                                    {caseHeader.allergy && (
                                        <Badge variant="destructive" className="gap-1 rounded-lg">
                                            <AlertTriangle className="h-3 w-3" aria-hidden="true" />
                                            Alergi: {caseHeader.allergy}
                                        </Badge>
                                    )}
                                </div>
                                <div className="text-muted-foreground flex flex-wrap gap-x-4 gap-y-1 text-xs">
                                    <span>
                                        No. Kasus: <strong className="text-foreground tabular-nums">{caseHeader.case_number}</strong>
                                    </span>
                                    <span>
                                        Diagnosis: <strong className="text-foreground">{caseHeader.diagnosis}</strong>
                                    </span>
                                    {caseHeader.dpjp_name && (
                                        <span>
                                            DPJP: <strong className="text-foreground">{caseHeader.dpjp_name}</strong>
                                        </span>
                                    )}
                                    {caseHeader.igd_visit_number && (
                                        <span>
                                            No. IGD: <strong className="text-foreground tabular-nums">{caseHeader.igd_visit_number}</strong>
                                        </span>
                                    )}
                                    <span>
                                        Operasi: <strong className="text-foreground">{caseHeader.surgery_type}</strong>
                                    </span>
                                    <span>
                                        Ruang:{' '}
                                        <strong className="text-foreground">{caseHeader.room ? caseHeader.room.code : 'Belum ditetapkan'}</strong>
                                    </span>
                                    <span>
                                        Operator: <strong className="text-foreground">{caseHeader.team.surgeon ?? 'Belum ditetapkan'}</strong>
                                    </span>
                                </div>
                            </div>
                            <div className="flex shrink-0 items-center gap-2">
                                <Button asChild variant="outline" size="sm" className="bg-card min-h-11 rounded-xl">
                                    <a href={route('cases.pdf.full', caseHeader.id)}>
                                        <Download className="mr-1.5 h-4 w-4" aria-hidden="true" />
                                        Ekspor PDF
                                    </a>
                                </Button>
                            </div>
                        </div>

                        {/* Stepper */}
                        <nav
                            aria-label="Tahapan kasus operasi"
                            className="flex gap-1.5 overflow-x-auto pb-1 [scrollbar-width:none] sm:grid sm:grid-cols-5 sm:gap-2 sm:overflow-visible sm:pb-0 [&::-webkit-scrollbar]:hidden"
                        >
                            {caseHeader.stages.map((stage, index) => (
                                <Link
                                    key={stage.key}
                                    href={stage.href}
                                    aria-current={stage.active ? 'step' : undefined}
                                    className={cn(
                                        'liquid-morph-pill flex min-h-11 shrink-0 items-center gap-2 rounded-2xl border px-3 py-1.5 text-xs sm:shrink',
                                        stage.completed
                                            ? 'border-primary/25 bg-secondary text-foreground font-medium'
                                            : stage.active
                                              ? 'border-primary bg-primary text-primary-foreground font-semibold shadow-xs'
                                              : 'border-border bg-card/85 text-muted-foreground hover:bg-card hover:text-foreground',
                                    )}
                                >
                                    <span
                                        aria-hidden="true"
                                        className={cn(
                                            'flex h-5 w-5 shrink-0 items-center justify-center rounded-full text-[11px] font-semibold',
                                            stage.completed
                                                ? 'bg-primary text-primary-foreground'
                                                : stage.active
                                                  ? 'bg-primary-foreground text-primary'
                                                  : 'bg-secondary text-foreground',
                                        )}
                                    >
                                        {stage.completed ? <Check className="h-3 w-3" /> : index + 1}
                                    </span>
                                    <span className="truncate">{stage.label}</span>
                                    {stage.locked && <Lock className="ml-auto h-3 w-3 shrink-0 opacity-60" aria-hidden="true" />}
                                </Link>
                            ))}
                        </nav>

                        {/* Floating Liquid Glass Pill Tabs */}
                        <nav
                            aria-label="Menu bagian rekam kasus"
                            className="bg-secondary/75 flex gap-1 overflow-x-auto rounded-2xl p-1 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
                        >
                            {tabs.map((tab) => {
                                const isActive = activeTab === tab.key;

                                return (
                                    <Link
                                        key={tab.key}
                                        href={tab.href}
                                        aria-current={isActive ? 'page' : undefined}
                                        className={cn(
                                            'liquid-morph-pill inline-flex min-h-11 shrink-0 items-center gap-1.5 rounded-xl px-3.5 py-2 text-sm whitespace-nowrap',
                                            isActive
                                                ? 'bg-primary text-primary-foreground font-semibold shadow-xs'
                                                : tab.locked
                                                  ? 'text-muted-foreground hover:bg-card/80 hover:text-foreground'
                                                  : 'text-foreground/85 hover:bg-card hover:text-foreground',
                                        )}
                                    >
                                        <span>{tab.label}</span>
                                        {tab.locked && <Lock className="h-3 w-3 opacity-60" aria-hidden="true" />}
                                    </Link>
                                );
                            })}
                        </nav>
                    </div>
                </div>

                <div className="min-w-0 flex-1 p-3 md:p-4">{children}</div>
            </div>
        </AppLayout>
    );
}

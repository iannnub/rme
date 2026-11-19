import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Activity } from 'lucide-react';

export type VitalSignPoint = {
    id: number;
    recorded_at: string;
    recorded_at_full?: string;
    bp_systolic: number | null;
    bp_diastolic: number | null;
    pulse: number | null;
    temperature: number | null;
    spo2: number | null;
};

interface VitalSignChartProps {
    data: VitalSignPoint[];
    title?: string;
    description?: string;
}

export function VitalSignChart({
    data,
    title = 'Grafik Pemantauan TTV terhadap Waktu',
    description = 'Tren TD Sistolik, TD Diastolik, Denyut Nadi, SpO₂ (%), dan Suhu (°C).',
}: VitalSignChartProps) {
    if (data.length === 0) {
        return (
            <Card>
                <CardHeader>
                    <CardTitle className="flex items-center gap-2 text-sm">
                        <Activity className="text-primary h-4 w-4" />
                        {title}
                    </CardTitle>
                    <CardDescription>{description}</CardDescription>
                </CardHeader>
                <CardContent>
                    <div className="text-muted-foreground rounded-md border border-dashed py-8 text-center text-xs">
                        Tambahkan minimal satu baris pemantauan TTV untuk menampilkan grafik tren.
                    </div>
                </CardContent>
            </Card>
        );
    }

    const width = 760;
    const height = 240;
    const padLeft = 46;
    const padRight = 24;
    const padTop = 20;
    const padBottom = 34;
    const chartW = width - padLeft - padRight;
    const chartH = height - padTop - padBottom;

    const maxY = 200;
    const minY = 30;

    const xFor = (idx: number): number => {
        if (data.length === 1) {
            return padLeft + chartW / 2;
        }
        return padLeft + (idx / (data.length - 1)) * chartW;
    };

    const yFor = (val: number): number => {
        const clamped = Math.max(minY, Math.min(maxY, val));
        return padTop + ((maxY - clamped) / (maxY - minY)) * chartH;
    };

    const buildPath = (getter: (p: VitalSignPoint) => number | null): string => {
        let path = '';
        data.forEach((p, i) => {
            const v = getter(p);
            if (v === null) {
                return;
            }
            const cmd = path === '' ? 'M' : 'L';
            path += `${cmd} ${xFor(i).toFixed(1)} ${yFor(v).toFixed(1)} `;
        });
        return path.trim();
    };

    const series: Array<{
        key: string;
        label: string;
        color: string;
        getter: (p: VitalSignPoint) => number | null;
        unit: string;
    }> = [
        { key: 'sys', label: 'TD Sistolik', color: '#dc2626', getter: (p) => p.bp_systolic, unit: 'mmHg' },
        { key: 'dia', label: 'TD Diastolik', color: '#f97316', getter: (p) => p.bp_diastolic, unit: 'mmHg' },
        { key: 'pulse', label: 'Nadi', color: '#2563eb', getter: (p) => p.pulse, unit: 'x/mnt' },
        { key: 'spo2', label: 'SpO₂', color: '#059669', getter: (p) => p.spo2, unit: '%' },
        { key: 'temp', label: 'Suhu (×1)', color: '#7c3aed', getter: (p) => p.temperature, unit: '°C' },
    ];

    const yTicks = [40, 80, 120, 160, 200];

    return (
        <Card>
            <CardHeader className="pb-2">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <CardTitle className="flex items-center gap-2 text-sm">
                            <Activity className="text-primary h-4 w-4" />
                            {title}
                        </CardTitle>
                        <CardDescription>{description}</CardDescription>
                    </div>
                    <div className="flex flex-wrap gap-3 text-xs">
                        {series.map((s) => (
                            <span key={s.key} className="inline-flex items-center gap-1.5">
                                <span className="inline-block h-2.5 w-2.5 rounded-full" style={{ backgroundColor: s.color }} />
                                {s.label}
                            </span>
                        ))}
                    </div>
                </div>
            </CardHeader>
            <CardContent>
                <div className="w-full overflow-x-auto">
                    <svg viewBox={`0 0 ${width} ${height}`} className="h-60 w-full min-w-[600px]" role="img" aria-label={title}>
                        {/* Horizontal grid lines */}
                        {yTicks.map((tick) => {
                            const y = yFor(tick);
                            return (
                                <g key={tick}>
                                    <line x1={padLeft} y1={y} x2={width - padRight} y2={y} stroke="currentColor" strokeOpacity={0.12} />
                                    <text x={padLeft - 8} y={y + 4} textAnchor="end" fontSize="10" fill="currentColor" opacity={0.65}>
                                        {tick}
                                    </text>
                                </g>
                            );
                        })}

                        {/* X axis labels */}
                        {data.map((p, idx) => (
                            <g key={p.id}>
                                <line
                                    x1={xFor(idx)}
                                    y1={padTop}
                                    x2={xFor(idx)}
                                    y2={height - padBottom}
                                    stroke="currentColor"
                                    strokeOpacity={0.07}
                                    strokeDasharray="3 3"
                                />
                                <text x={xFor(idx)} y={height - 10} textAnchor="middle" fontSize="10" fill="currentColor" opacity={0.75}>
                                    {p.recorded_at}
                                </text>
                            </g>
                        ))}

                        {/* Lines & Points */}
                        {series.map((s) => {
                            const d = buildPath(s.getter);
                            return (
                                <g key={s.key}>
                                    {d && <path d={d} fill="none" stroke={s.color} strokeWidth={2} strokeLinejoin="round" strokeLinecap="round" />}
                                    {data.map((p, idx) => {
                                        const val = s.getter(p);
                                        if (val === null) {
                                            return null;
                                        }
                                        return (
                                            <circle key={`${s.key}-${p.id}`} cx={xFor(idx)} cy={yFor(val)} r={3.5} fill={s.color}>
                                                <title>{`${p.recorded_at} - ${s.label}: ${val} ${s.unit}`}</title>
                                            </circle>
                                        );
                                    })}
                                </g>
                            );
                        })}
                    </svg>
                </div>
            </CardContent>
        </Card>
    );
}

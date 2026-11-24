import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Syringe } from 'lucide-react';
import { type PreopClinicalDetails, type UpdateClinicalFieldFn } from './types';

interface PreopAnesthesiaCardProps {
    details: PreopClinicalDetails;
    onChange: UpdateClinicalFieldFn;
    disabled: boolean;
}

export function PreopAnesthesiaCard({ details, onChange, disabled }: PreopAnesthesiaCardProps) {
    return (
        <Card>
            <CardHeader>
                <CardTitle className="flex items-center gap-2 text-lg">
                    <Syringe className="text-primary h-5 w-5" />
                    Pengkajian Pra-Anestesi &amp; Catatan Pelaksanaan Anestesi
                </CardTitle>
                <CardDescription>
                    Evaluasi jalan napas, klasifikasi ASA, rencana anestesi, serta catatan teknis pelaksanaan anestesi di kamar operasi.
                </CardDescription>
            </CardHeader>
            <CardContent className="space-y-6">
                <div className="space-y-3">
                    <h4 className="text-foreground border-b pb-1 text-sm font-semibold">1. Pengkajian Pra-Anestesi</h4>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-1.5">
                            <Label htmlFor="an_airway_exam">Evaluasi Jalan Napas (Mallampati / Gigi / Leher)</Label>
                            <Input
                                id="an_airway_exam"
                                value={details.anesthesia_pre.airway_exam}
                                onChange={(e) => onChange('anesthesia_pre', 'airway_exam', e.target.value)}
                                disabled={disabled}
                                placeholder="Mallampati II, buka mulut >= 3 jari, leher bebas"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="an_cardiopulm">Fungsi Jantung &amp; Paru</Label>
                            <Input
                                id="an_cardiopulm"
                                value={details.anesthesia_pre.cardiopulmonary}
                                onChange={(e) => onChange('anesthesia_pre', 'cardiopulmonary', e.target.value)}
                                disabled={disabled}
                                placeholder="BJ I-II reguler, vesikuler +/+, ronki -/-"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="an_asa_class">Klasifikasi Status Fisik ASA</Label>
                            <Input
                                id="an_asa_class"
                                value={details.anesthesia_pre.asa_class}
                                onChange={(e) => onChange('anesthesia_pre', 'asa_class', e.target.value)}
                                disabled={disabled}
                                placeholder="ASA II E / III E"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="an_planned">Rencana Metode &amp; Obat Anestesi</Label>
                            <Input
                                id="an_planned"
                                value={details.anesthesia_pre.planned_anesthesia}
                                onChange={(e) => onChange('anesthesia_pre', 'planned_anesthesia', e.target.value)}
                                disabled={disabled}
                                placeholder="Anestesi Umum (ETT) / Regional Spinal"
                            />
                        </div>
                    </div>
                </div>

                <div className="space-y-3">
                    <h4 className="text-foreground border-b pb-1 text-sm font-semibold">2. Catatan Pelaksanaan &amp; Pengelolaan Anestesi</h4>
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <div className="space-y-1.5">
                            <Label htmlFor="an_method">Jenis Anestesi Diberikan</Label>
                            <Input
                                id="an_method"
                                value={details.anesthesia_exec.anesthesia_method}
                                onChange={(e) => onChange('anesthesia_exec', 'anesthesia_method', e.target.value)}
                                disabled={disabled}
                                placeholder="General Anesthesia (Intubasi ETT)"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="an_start_time">Waktu Mulai Anestesi</Label>
                            <Input
                                id="an_start_time"
                                value={details.anesthesia_exec.anesthesia_start_time}
                                onChange={(e) => onChange('anesthesia_exec', 'anesthesia_start_time', e.target.value)}
                                disabled={disabled}
                                placeholder="contoh: 10:45 WIB"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="an_end_time">Waktu Selesai Anestesi</Label>
                            <Input
                                id="an_end_time"
                                value={details.anesthesia_exec.anesthesia_end_time}
                                onChange={(e) => onChange('anesthesia_exec', 'anesthesia_end_time', e.target.value)}
                                disabled={disabled}
                                placeholder="contoh: 13:15 WIB"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="an_ett_type">Jenis &amp; Ukuran ETT / LMA</Label>
                            <Input
                                id="an_ett_type"
                                value={details.anesthesia_exec.ett_type_size}
                                onChange={(e) => onChange('anesthesia_exec', 'ett_type_size', e.target.value)}
                                disabled={disabled}
                                placeholder="ETT Kinking/Non-Kinking No. 7.5"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="an_ett_depth">Kedalaman ETT (cm)</Label>
                            <Input
                                id="an_ett_depth"
                                value={details.anesthesia_exec.ett_depth_cm}
                                onChange={(e) => onChange('anesthesia_exec', 'ett_depth_cm', e.target.value)}
                                disabled={disabled}
                                placeholder="21 cm di batas bibir"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="an_airway_aid">Alat Bantu Jalan Napas Tambahan</Label>
                            <Input
                                id="an_airway_aid"
                                value={details.anesthesia_exec.airway_aid}
                                onChange={(e) => onChange('anesthesia_exec', 'airway_aid', e.target.value)}
                                disabled={disabled}
                                placeholder="Oropharyngeal Airway (Guedel) No. 4"
                            />
                        </div>
                        <div className="space-y-1.5 sm:col-span-2 lg:col-span-3">
                            <Label htmlFor="an_ventilator">Pengaturan Ventilator</Label>
                            <Input
                                id="an_ventilator"
                                value={details.anesthesia_exec.ventilator_setting}
                                onChange={(e) => onChange('anesthesia_exec', 'ventilator_setting', e.target.value)}
                                disabled={disabled}
                                placeholder="VCV TV 450 mL, RR 12x/mnt, FiO2 50%, PEEP 5 cmH2O"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="an_meds">Obat Anestesi Diberikan</Label>
                            <Input
                                id="an_meds"
                                value={details.anesthesia_exec.medications_summary}
                                onChange={(e) => onChange('anesthesia_exec', 'medications_summary', e.target.value)}
                                disabled={disabled}
                                placeholder="Propofol, Fentanyl, Rocuronium, Sevoflurane"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="an_med_dose">Dosis Obat</Label>
                            <Input
                                id="an_med_dose"
                                value={details.anesthesia_exec.medication_dose}
                                onChange={(e) => onChange('anesthesia_exec', 'medication_dose', e.target.value)}
                                disabled={disabled}
                                placeholder="Propofol 120 mg, Fentanyl 100 mcg"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="an_med_time">Waktu Pemberian Obat</Label>
                            <Input
                                id="an_med_time"
                                value={details.anesthesia_exec.medication_time}
                                onChange={(e) => onChange('anesthesia_exec', 'medication_time', e.target.value)}
                                disabled={disabled}
                                placeholder="10:45 WIB (Induksi)"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="an_fluids">Cairan Diberikan (Anestesi)</Label>
                            <Input
                                id="an_fluids"
                                value={details.anesthesia_exec.fluids_given}
                                onChange={(e) => onChange('anesthesia_exec', 'fluids_given', e.target.value)}
                                disabled={disabled}
                                placeholder="NaCl 0.9% 1000 mL"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="an_vitals_note">Catatan Pemantauan Tanda Vital</Label>
                            <Input
                                id="an_vitals_note"
                                value={details.anesthesia_exec.vitals_monitoring_note}
                                onChange={(e) => onChange('anesthesia_exec', 'vitals_monitoring_note', e.target.value)}
                                disabled={disabled}
                                placeholder="Hemodinamik stabil selama induksi dan pemeliharaan"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="an_complications">Komplikasi selama Anestesi</Label>
                            <Input
                                id="an_complications"
                                value={details.anesthesia_exec.complications}
                                onChange={(e) => onChange('anesthesia_exec', 'complications', e.target.value)}
                                disabled={disabled}
                                placeholder="Tidak ada komplikasi anestesi"
                            />
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>
    );
}

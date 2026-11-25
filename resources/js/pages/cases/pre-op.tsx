import { PreopAnesthesiaCard } from '@/components/cases/preop/PreopAnesthesiaCard';
import { PreopEdhForm } from '@/components/cases/preop/PreopEdhForm';
import { PreopNeuroDetailsCard } from '@/components/cases/preop/PreopNeuroDetailsCard';
import { PreopOrthoDetailsCard } from '@/components/cases/preop/PreopOrthoDetailsCard';
import { PreopOrthoForm } from '@/components/cases/preop/PreopOrthoForm';
import { type PreopClinicalDetails, type UpdateClinicalFieldFn } from '@/components/cases/preop/types';
import { ConcurrencyAlert } from '@/components/concurrency-alert';
import InputError from '@/components/input-error';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import CaseLayout, { type CaseHeaderData } from '@/layouts/case-layout';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import { Activity, AlertTriangle, CheckCircle2, FileCheck, HeartPulse, Plus, Save, ShieldAlert, Trash2, UserCheck } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

type OptionItem = {
    value: string;
    label: string;
};

type MasterItem = {
    id: number;
    name: string;
    code?: string;
    default_medical_diagnosis_id?: number | null;
    default_procedure_name?: string | null;
};

type SupportingExamItem = {
    id?: number;
    exam_type: string;
    result: string;
    exam_date: string;
};

type CaseInterventionItemView = {
    id?: number;
    siki_item_id: number;
    section: string;
    section_label: string;
    text: string;
    is_done: boolean;
    done_at?: string;
};

type SikiInterventionView = {
    id: number;
    name: string;
    items: CaseInterventionItemView[];
};

type SlkiCriterionView = {
    id: number;
    outcome: string;
    text: string;
    target: number;
};

type CaseNursingDiagnosisView = {
    id: number;
    name: string;
    code?: string | null;
    custom_text?: string | null;
    notes?: string | null;
    criteria: SlkiCriterionView[];
    interventions: SikiInterventionView[];
};

interface PreopPageProps {
    caseHeader: CaseHeaderData;
    casePlan: {
        medical_diagnosis_id: number | null;
        medical_diagnosis_other: string | null;
        surgery_type_id: number | null;
        surgery_type_other: string | null;
        surgery_site: string | null;
        anesthesia_type_id: number | null;
        surgeon_id: number | null;
        anesthesiologist_id: number | null;
    };
    assessment: {
        id?: number;
        updated_at?: string | null;
        chief_complaint: string;
        current_illness_history: string;
        past_illness_history: string;
        surgery_history: string;
        allergy_status: string;
        allergy_detail: string;
        medication_history: string;
        anesthesia_history: string;
        fasting_status: boolean;
        fasting_since: string;
        skin_condition: string;
        anxiety_level: string;
        surgery_knowledge: string;
        family_support: string;
        mental_readiness: string;
        preparation: {
            identity_verified: boolean;
            informed_consent_surgery: boolean;
            anesthesia_consent: boolean;
            fasting_verified: boolean;
            allergy_assessed: boolean;
            lab_available: boolean;
            iv_access: boolean;
            operation_area_prepared: boolean;
            jewelry_removed: boolean;
            site_marked: boolean | null;
        };
        implementation_actions: string;
        implementation_education: string;
        implementation_monitoring: string;
        implementation_response: string;
        evaluation_condition: string;
        evaluation_completeness: string;
        readiness_status: string;
        evaluation_notes: string;
        clinical_details: PreopClinicalDetails;
    };
    vitalSigns: {
        id?: number;
        recorded_at: string;
        bp_systolic: number | string;
        bp_diastolic: number | string;
        pulse: number | string;
        temperature: number | string;
        respiratory_rate: number | string;
        spo2: number | string;
        weight_kg: number | string;
        height_cm: number | string;
        consciousness: string;
        notes: string;
    };
    supportingExams: SupportingExamItem[];
    caseNursingDiagnoses: CaseNursingDiagnosisView[];
    signoffState: {
        nurseSignoff: {
            id: number;
            user_name: string;
            nip?: string | null;
            signed_at: string;
        } | null;
        doctorVerification: {
            id: number;
            user_name: string;
            sip?: string | null;
            signed_at: string;
        } | null;
        revokedVerifications: Array<{
            id: number;
            user_name: string;
            signed_at: string;
            revoked_at: string;
            revoke_reason: string | null;
        }>;
    };
    options: {
        medicalDiagnoses: MasterItem[];
        surgeryTypes: MasterItem[];
        anesthesiaTypes: MasterItem[];
        surgeons: MasterItem[];
        anesthesiologists: MasterItem[];
        masterNursingDiagnoses: MasterItem[];
        examTypes: OptionItem[];
        allergyStatuses: OptionItem[];
        anxietyLevels: OptionItem[];
        readinessStatuses: OptionItem[];
        consciousnessList: OptionItem[];
    };
    canManage: boolean;
    canSign: boolean;
    canVerify: boolean;
    prepProgress?: {
        instrument_checks_count: number;
        instrument_checks_ready: boolean;
        ssc_sign_in_status: string;
        ssc_sign_in_label: string;
    };
}

export default function PreopPage({
    caseHeader,
    casePlan,
    assessment,
    vitalSigns,
    supportingExams,
    caseNursingDiagnoses,
    signoffState,
    options,
    canManage,
    canSign,
    canVerify,
    prepProgress,
}: PreopPageProps) {
    const { flash } = usePage<{ flash?: { success?: string; error?: string } }>().props;

    const [signDialogOpen, setSignDialogOpen] = useState(false);
    const [verifyDialogOpen, setVerifyDialogOpen] = useState(false);
    const [addDiagDialogOpen, setAddDiagDialogOpen] = useState(false);

    const [exams, setExams] = useState<SupportingExamItem[]>(
        supportingExams.length > 0 ? supportingExams : [{ exam_type: 'hemoglobin', result: '', exam_date: '' }],
    );

    const mainForm = useForm({
        // Plan
        medical_diagnosis_id: casePlan.medical_diagnosis_id ? String(casePlan.medical_diagnosis_id) : casePlan.medical_diagnosis_other ? 'other' : '',
        medical_diagnosis_other: casePlan.medical_diagnosis_other ?? '',
        surgery_type_id: casePlan.surgery_type_id ? String(casePlan.surgery_type_id) : casePlan.surgery_type_other ? 'other' : '',
        surgery_type_other: casePlan.surgery_type_other ?? '',
        surgery_site: casePlan.surgery_site ?? '',
        anesthesia_type_id: casePlan.anesthesia_type_id ? String(casePlan.anesthesia_type_id) : '',
        surgeon_id: casePlan.surgeon_id ? String(casePlan.surgeon_id) : '',
        anesthesiologist_id: casePlan.anesthesiologist_id ? String(casePlan.anesthesiologist_id) : '',

        // Asesmen
        chief_complaint: assessment.chief_complaint,
        current_illness_history: assessment.current_illness_history,
        past_illness_history: assessment.past_illness_history,
        surgery_history: assessment.surgery_history,
        allergy_status: assessment.allergy_status,
        allergy_detail: assessment.allergy_detail,
        medication_history: assessment.medication_history,
        anesthesia_history: assessment.anesthesia_history,
        fasting_status: assessment.fasting_status,
        fasting_since: assessment.fasting_since,
        skin_condition: assessment.skin_condition,
        anxiety_level: assessment.anxiety_level,
        surgery_knowledge: assessment.surgery_knowledge,
        family_support: assessment.family_support,
        mental_readiness: assessment.mental_readiness,

        // Checklist persiapan (10 item)
        preparation: {
            identity_verified: assessment.preparation.identity_verified,
            informed_consent_surgery: assessment.preparation.informed_consent_surgery,
            anesthesia_consent: assessment.preparation.anesthesia_consent,
            fasting_verified: assessment.preparation.fasting_verified,
            allergy_assessed: assessment.preparation.allergy_assessed,
            lab_available: assessment.preparation.lab_available,
            iv_access: assessment.preparation.iv_access,
            operation_area_prepared: assessment.preparation.operation_area_prepared,
            jewelry_removed: assessment.preparation.jewelry_removed,
            site_marked: assessment.preparation.site_marked,
        },

        // Implementasi & Evaluasi
        implementation_actions: assessment.implementation_actions,
        implementation_education: assessment.implementation_education,
        implementation_monitoring: assessment.implementation_monitoring,
        implementation_response: assessment.implementation_response,
        evaluation_condition: assessment.evaluation_condition,
        evaluation_completeness: assessment.evaluation_completeness,
        readiness_status: assessment.readiness_status,
        evaluation_notes: assessment.evaluation_notes,
        clinical_details: assessment.clinical_details,

        // TTV
        vital_signs: {
            recorded_at: vitalSigns.recorded_at,
            bp_systolic: vitalSigns.bp_systolic,
            bp_diastolic: vitalSigns.bp_diastolic,
            pulse: vitalSigns.pulse,
            temperature: vitalSigns.temperature,
            respiratory_rate: vitalSigns.respiratory_rate,
            spo2: vitalSigns.spo2,
            weight_kg: vitalSigns.weight_kg,
            height_cm: vitalSigns.height_cm,
            consciousness: vitalSigns.consciousness,
            notes: vitalSigns.notes,
        },

        // Penunjang
        supporting_exams: exams,
    });

    const updateClinicalField: UpdateClinicalFieldFn = (section, field, value) => {
        mainForm.setData('clinical_details', {
            ...mainForm.data.clinical_details,
            [section]: {
                ...mainForm.data.clinical_details[section],
                [field]: value,
            },
        });
    };

    const selectedSurgeryType = options.surgeryTypes.find((t) => String(t.id) === mainForm.data.surgery_type_id);
    const activeSurgeryProfile: 'bedah_saraf' | 'bedah_ortopedi' | null =
        selectedSurgeryType?.code?.toLowerCase() === 'bedah_saraf' || selectedSurgeryType?.name.toLowerCase().includes('saraf')
            ? 'bedah_saraf'
            : selectedSurgeryType?.code?.toLowerCase() === 'bedah_ortopedi' || selectedSurgeryType?.name.toLowerCase().includes('ortopedi')
              ? 'bedah_ortopedi'
              : caseHeader.surgery_profile;

    const signForm = useForm({
        password: '',
    });

    const verifyForm = useForm({
        password: '',
    });

    const diagForm = useForm({
        nursing_diagnosis_id: '',
        custom_text: '',
        notes: '',
    });

    const handleSaveMainForm: FormEventHandler = (e) => {
        e.preventDefault();
        mainForm.transform((data) => ({
            ...data,
            vital_signs: {
                ...data.vital_signs,
                recorded_at: data.vital_signs.recorded_at || new Date().toISOString().slice(0, 16),
            },
            expected_updated_at: assessment.updated_at ?? '',
            supporting_exams: exams.filter((x) => x.exam_type && x.result.trim() !== ''),
        }));
        mainForm.put(route('cases.preop.update', caseHeader.id), {
            preserveScroll: true,
        });
    };

    const handleSignSubmit: FormEventHandler = (e) => {
        e.preventDefault();
        signForm.post(route('cases.preop.sign', caseHeader.id), {
            onSuccess: () => {
                setSignDialogOpen(false);
                signForm.reset();
            },
        });
    };

    const handleVerifySubmit: FormEventHandler = (e) => {
        e.preventDefault();
        verifyForm.post(route('cases.preop.verify', caseHeader.id), {
            onSuccess: () => {
                setVerifyDialogOpen(false);
                verifyForm.reset();
            },
        });
    };

    const handleAddDiagSubmit: FormEventHandler = (e) => {
        e.preventDefault();
        diagForm.post(route('cases.preop.add-diagnosis', caseHeader.id), {
            onSuccess: () => {
                setAddDiagDialogOpen(false);
                diagForm.reset();
            },
        });
    };

    const handleToggleIntervention = (itemId?: number) => {
        if (!itemId || !canManage) return;
        router.post(route('cases.preop.toggle-intervention', [caseHeader.id, itemId]), {}, { preserveScroll: true });
    };

    const handleDeleteDiagnosis = (diagId: number) => {
        if (!canManage) return;
        if (!confirm('Hapus diagnosis keperawatan ini beserta daftar intervensinya?')) return;
        router.delete(route('cases.preop.remove-diagnosis', [caseHeader.id, diagId]), {
            preserveScroll: true,
        });
    };

    const addExamRow = () => {
        setExams((prev) => [...prev, { exam_type: 'hemoglobin', result: '', exam_date: '' }]);
    };

    const removeExamRow = (index: number) => {
        setExams((prev) => prev.filter((_, i) => i !== index));
    };

    const updateExamRow = (index: number, field: keyof SupportingExamItem, value: string) => {
        setExams((prev) => {
            const next = [...prev];
            next[index] = { ...next[index], [field]: value };
            return next;
        });
    };

    // Peringatan rentang wajar TTV (bukan blokir, hanya chip kuning sesuai KF-3)
    const bpSys = Number(mainForm.data.vital_signs.bp_systolic);
    const bpDia = Number(mainForm.data.vital_signs.bp_diastolic);
    const pulse = Number(mainForm.data.vital_signs.pulse);
    const temp = Number(mainForm.data.vital_signs.temperature);
    const rr = Number(mainForm.data.vital_signs.respiratory_rate);
    const spo2 = Number(mainForm.data.vital_signs.spo2);

    const bpWarning = Boolean((bpSys && (bpSys > 140 || bpSys < 90)) || (bpDia && (bpDia > 90 || bpDia < 60)));
    const pulseWarning = pulse && (pulse > 100 || pulse < 60);
    const tempWarning = temp && (temp > 37.5 || temp < 36.0);
    const rrWarning = rr && (rr > 22 || rr < 12);
    const spo2Warning = spo2 && spo2 < 95;

    return (
        <CaseLayout caseHeader={caseHeader} activeTab="pre-op">
            <Head title={`Pre-Op - ${caseHeader.case_number}`} />

            <div className="space-y-6">
                <ConcurrencyAlert message={(mainForm.errors as Record<string, string | undefined>).concurrency} />

                {/* Header Aksi & Informasi Status Pengesahan */}
                <div className="bg-card flex flex-col gap-4 rounded-xl border p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 className="text-foreground flex items-center gap-2 text-xl font-bold tracking-tight">
                            <Activity className="text-primary h-5 w-5" />
                            Pengkajian & Persiapan Pre-Operasi
                        </h2>
                        <p className="text-muted-foreground text-sm">
                            Dokumentasi asesmen awal, TTV, pemeriksaan penunjang, checklist kesiapan, dan diagnosis SDKI/SLKI/SIKI.
                        </p>
                    </div>

                    <div className="flex flex-wrap items-center gap-2">
                        {canManage && (
                            <Button type="button" onClick={handleSaveMainForm} disabled={mainForm.processing} className="gap-2">
                                <Save className="h-4 w-4" />
                                {mainForm.processing ? 'Menyimpan...' : 'Simpan Asesmen'}
                            </Button>
                        )}
                        {canSign && (
                            <Button
                                type="button"
                                variant={signoffState.nurseSignoff ? 'outline' : 'default'}
                                onClick={() => setSignDialogOpen(true)}
                                className={signoffState.nurseSignoff ? 'border-primary/40 text-primary hover:bg-primary/10 gap-2' : 'gap-2'}
                            >
                                <FileCheck className="h-4 w-4" />
                                {signoffState.nurseSignoff ? 'Sahkan Ulang Perawat' : 'Sahkan Pra-Operasi'}
                            </Button>
                        )}
                        {canVerify && (
                            <Button
                                type="button"
                                variant={signoffState.doctorVerification ? 'outline' : 'secondary'}
                                onClick={() => setVerifyDialogOpen(true)}
                                className="gap-2"
                            >
                                <UserCheck className="h-4 w-4" />
                                {signoffState.doctorVerification ? 'Verifikasi Ulang Dokter' : 'Verifikasi Dokter'}
                            </Button>
                        )}
                    </div>
                </div>

                {flash?.success && (
                    <Alert className="border-emerald-500/50 bg-emerald-50 text-emerald-900 dark:bg-emerald-950/20 dark:text-emerald-200">
                        <CheckCircle2 className="h-4 w-4 text-emerald-600 dark:text-emerald-400" />
                        <AlertTitle>Berhasil</AlertTitle>
                        <AlertDescription>{flash.success}</AlertDescription>
                    </Alert>
                )}

                {/* Status Kartu Pengesahan & Verifikasi */}
                <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <Card className="border-border">
                        <CardHeader className="pb-3">
                            <div className="flex items-center justify-between">
                                <CardTitle className="flex items-center gap-2 text-sm font-semibold">
                                    <FileCheck className="text-primary h-4 w-4" />
                                    Pengesahan Perawat Pra-Operasi
                                </CardTitle>
                                {signoffState.nurseSignoff ? (
                                    <Badge className="bg-emerald-600 text-white">Disahkan</Badge>
                                ) : (
                                    <Badge variant="outline" className="border-amber-300 text-amber-600">
                                        Belum Disahkan
                                    </Badge>
                                )}
                            </div>
                        </CardHeader>
                        <CardContent className="text-muted-foreground space-y-1 text-xs">
                            {signoffState.nurseSignoff ? (
                                <>
                                    <p className="text-foreground font-medium">Oleh: {signoffState.nurseSignoff.user_name}</p>
                                    <p>NIP: {signoffState.nurseSignoff.nip || '-'}</p>
                                    <p>Waktu: {signoffState.nurseSignoff.signed_at} WIB</p>
                                </>
                            ) : (
                                <p>Pengesahan oleh perawat merupakan syarat wajib untuk memajukan status kasus ke Siap Operasi.</p>
                            )}
                        </CardContent>
                    </Card>

                    <Card className="border-border">
                        <CardHeader className="pb-3">
                            <div className="flex items-center justify-between">
                                <CardTitle className="flex items-center gap-2 text-sm font-semibold">
                                    <UserCheck className="text-primary h-4 w-4" />
                                    Verifikasi Dokter (Paralel)
                                </CardTitle>
                                {signoffState.doctorVerification ? (
                                    <Badge className="bg-emerald-600 text-white">Terverifikasi</Badge>
                                ) : (
                                    <Badge variant="outline" className="text-slate-500">
                                        Belum Diverifikasi
                                    </Badge>
                                )}
                            </div>
                        </CardHeader>
                        <CardContent className="text-muted-foreground space-y-1 text-xs">
                            {signoffState.doctorVerification ? (
                                <>
                                    <p className="text-foreground font-medium">Oleh: {signoffState.doctorVerification.user_name}</p>
                                    <p>SIP: {signoffState.doctorVerification.sip || '-'}</p>
                                    <p>Waktu: {signoffState.doctorVerification.signed_at} WIB</p>
                                </>
                            ) : (
                                <p>
                                    Dokter operator atau dokter anestesi memverifikasi rencana tindakan dan asesmen secara paralel tanpa memblokir
                                    transfer pasien.
                                </p>
                            )}

                            {signoffState.revokedVerifications.length > 0 && (
                                <div className="mt-2 border-t pt-2 text-[11px] text-amber-700 dark:text-amber-400">
                                    <p className="font-medium">Catatan reset verifikasi:</p>
                                    <p>Verifikasi sebelumnya dibatalkan otomatis karena data diperbarui perawat.</p>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>

                <form onSubmit={handleSaveMainForm} className="space-y-6">
                    {activeSurgeryProfile === 'bedah_saraf' ? (
                        <PreopEdhForm
                            caseHeader={caseHeader}
                            prepProgress={
                                prepProgress ?? {
                                    instrument_checks_count: 0,
                                    instrument_checks_ready: false,
                                    ssc_sign_in_status: 'belum',
                                    ssc_sign_in_label: 'Belum Diisi',
                                }
                            }
                            data={mainForm.data}
                            errors={mainForm.errors as Record<string, string | undefined>}
                            options={options}
                            canManage={canManage}
                            onSetField={(field, value) => mainForm.setData((prev) => ({ ...prev, [field]: value }))}
                            onUpdateClinical={updateClinicalField}
                            onUpdateVital={(field, value) =>
                                mainForm.setData('vital_signs', {
                                    ...mainForm.data.vital_signs,
                                    [field]: value,
                                })
                            }
                            onTogglePrep={(field, checked) =>
                                mainForm.setData('preparation', {
                                    ...mainForm.data.preparation,
                                    [field]: checked,
                                })
                            }
                        />
                    ) : activeSurgeryProfile === 'bedah_ortopedi' ? (
                        <PreopOrthoForm
                            caseHeader={caseHeader}
                            prepProgress={
                                prepProgress ?? {
                                    instrument_checks_count: 0,
                                    instrument_checks_ready: false,
                                    ssc_sign_in_status: 'belum',
                                    ssc_sign_in_label: 'Belum Diisi',
                                }
                            }
                            data={mainForm.data}
                            errors={mainForm.errors as Record<string, string | undefined>}
                            options={options}
                            canManage={canManage}
                            onSetField={(field, value) => mainForm.setData((prev) => ({ ...prev, [field]: value }))}
                            onUpdateClinical={updateClinicalField}
                            onUpdateVital={(field, value) =>
                                mainForm.setData('vital_signs', {
                                    ...mainForm.data.vital_signs,
                                    [field]: value,
                                })
                            }
                            onTogglePrep={(field, checked) =>
                                mainForm.setData('preparation', {
                                    ...mainForm.data.preparation,
                                    [field]: checked,
                                })
                            }
                        />
                    ) : (
                        <>
                            {/* BAGIAN A: Rencana Tindakan Operasi */}
                            <Card>
                                <CardHeader className="pb-4">
                                    <CardTitle className="text-base font-semibold">A. Rencana Tindakan Operasi</CardTitle>
                                    <CardDescription>Diagnosis medis, prosedur bedah yang direncanakan, dan dokter penanggung jawab.</CardDescription>
                                </CardHeader>
                                <CardContent className="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                                    <div className="space-y-2">
                                        <Label htmlFor="med_diag">Diagnosis Medis</Label>
                                        <Select
                                            value={mainForm.data.medical_diagnosis_id}
                                            onValueChange={(val) => mainForm.setData('medical_diagnosis_id', val)}
                                            disabled={!canManage}
                                        >
                                            <SelectTrigger id="med_diag">
                                                <SelectValue placeholder="Pilih Diagnosis Medis" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {options.medicalDiagnoses.map((d) => (
                                                    <SelectItem key={d.id} value={String(d.id)}>
                                                        {d.name}
                                                    </SelectItem>
                                                ))}
                                                <SelectItem value="other">Diagnosis Lainnya (Ketik Manual)</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>

                                    {mainForm.data.medical_diagnosis_id === 'other' && (
                                        <div className="space-y-2">
                                            <Label htmlFor="med_diag_other">Diagnosis Medis Lainnya</Label>
                                            <Input
                                                id="med_diag_other"
                                                value={mainForm.data.medical_diagnosis_other}
                                                onChange={(e) => mainForm.setData('medical_diagnosis_other', e.target.value)}
                                                disabled={!canManage}
                                                placeholder="Ketik diagnosis medis..."
                                            />
                                        </div>
                                    )}

                                    <div className="space-y-2">
                                        <Label htmlFor="surg_type">Jenis Operasi</Label>
                                        <Select
                                            value={mainForm.data.surgery_type_id}
                                            onValueChange={(val) => {
                                                const chosen = options.surgeryTypes.find((t) => String(t.id) === val);
                                                mainForm.setData((prev) => ({
                                                    ...prev,
                                                    surgery_type_id: val,
                                                    medical_diagnosis_id: chosen?.default_medical_diagnosis_id
                                                        ? String(chosen.default_medical_diagnosis_id)
                                                        : prev.medical_diagnosis_id,
                                                }));
                                            }}
                                            disabled={!canManage}
                                        >
                                            <SelectTrigger id="surg_type">
                                                <SelectValue placeholder="Pilih Jenis Operasi" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {options.surgeryTypes.map((t) => (
                                                    <SelectItem key={t.id} value={String(t.id)}>
                                                        {t.name}
                                                    </SelectItem>
                                                ))}
                                                <SelectItem value="other">Jenis Operasi Lainnya</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>

                                    {mainForm.data.surgery_type_id === 'other' && (
                                        <div className="space-y-2">
                                            <Label htmlFor="surg_type_other">Jenis Operasi Lainnya</Label>
                                            <Input
                                                id="surg_type_other"
                                                value={mainForm.data.surgery_type_other}
                                                onChange={(e) => mainForm.setData('surgery_type_other', e.target.value)}
                                                disabled={!canManage}
                                                placeholder="Ketik jenis operasi..."
                                            />
                                        </div>
                                    )}

                                    <div className="space-y-2">
                                        <Label htmlFor="surgery_site">Lokasi / Sisi Operasi</Label>
                                        <Input
                                            id="surgery_site"
                                            value={mainForm.data.surgery_site}
                                            onChange={(e) => mainForm.setData('surgery_site', e.target.value)}
                                            disabled={!canManage}
                                            placeholder="Contoh: Abdomen kanan bawah"
                                        />
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="anest_type">Jenis Anestesi</Label>
                                        <Select
                                            value={mainForm.data.anesthesia_type_id}
                                            onValueChange={(val) => mainForm.setData('anesthesia_type_id', val)}
                                            disabled={!canManage}
                                        >
                                            <SelectTrigger id="anest_type">
                                                <SelectValue placeholder="Pilih Jenis Anestesi" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {options.anesthesiaTypes.map((a) => (
                                                    <SelectItem key={a.id} value={String(a.id)}>
                                                        {a.name} ({a.code})
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="surgeon_id">Dokter Operator</Label>
                                        <Select
                                            value={mainForm.data.surgeon_id}
                                            onValueChange={(val) => mainForm.setData('surgeon_id', val)}
                                            disabled={!canManage}
                                        >
                                            <SelectTrigger id="surgeon_id">
                                                <SelectValue placeholder="Pilih Dokter Operator" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {options.surgeons.map((s) => (
                                                    <SelectItem key={s.id} value={String(s.id)}>
                                                        {s.name}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="anesthesiologist_id">Dokter Anestesi</Label>
                                        <Select
                                            value={mainForm.data.anesthesiologist_id}
                                            onValueChange={(val) => mainForm.setData('anesthesiologist_id', val)}
                                            disabled={!canManage}
                                        >
                                            <SelectTrigger id="anesthesiologist_id">
                                                <SelectValue placeholder="Pilih Dokter Anestesi" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {options.anesthesiologists.map((a) => (
                                                    <SelectItem key={a.id} value={String(a.id)}>
                                                        {a.name}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>
                                </CardContent>
                            </Card>

                            {/* BAGIAN B: Asesmen Praoperasi */}
                            <Card>
                                <CardHeader className="pb-4">
                                    <CardTitle className="text-base font-semibold">B. Asesmen Praoperasi (Anamnesis & Riwayat)</CardTitle>
                                    <CardDescription>Riwayat keluhan, alergi, puasa, riwayat anestesi, dan pengobatan.</CardDescription>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                                        <div className="space-y-2">
                                            <Label htmlFor="chief_complaint">Keluhan Utama</Label>
                                            <Input
                                                id="chief_complaint"
                                                value={mainForm.data.chief_complaint}
                                                onChange={(e) => mainForm.setData('chief_complaint', e.target.value)}
                                                disabled={!canManage}
                                                placeholder="Keluhan utama pasien saat ini..."
                                            />
                                            <InputError message={mainForm.errors.chief_complaint} />
                                        </div>

                                        <div className="space-y-2">
                                            <Label htmlFor="current_illness_history">Riwayat Penyakit Sekarang</Label>
                                            <Input
                                                id="current_illness_history"
                                                value={mainForm.data.current_illness_history}
                                                onChange={(e) => mainForm.setData('current_illness_history', e.target.value)}
                                                disabled={!canManage}
                                                placeholder="Perjalanan penyakit yang dialami..."
                                            />
                                        </div>
                                    </div>

                                    <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                                        <div className="space-y-2">
                                            <Label htmlFor="past_illness_history">Riwayat Penyakit Dahulu</Label>
                                            <Input
                                                id="past_illness_history"
                                                value={mainForm.data.past_illness_history}
                                                onChange={(e) => mainForm.setData('past_illness_history', e.target.value)}
                                                disabled={!canManage}
                                                placeholder="Riwayat penyakit terdahulu..."
                                            />
                                        </div>

                                        <div className="space-y-2">
                                            <Label htmlFor="surgery_history">Riwayat Operasi Sebelumnya</Label>
                                            <Input
                                                id="surgery_history"
                                                value={mainForm.data.surgery_history}
                                                onChange={(e) => mainForm.setData('surgery_history', e.target.value)}
                                                disabled={!canManage}
                                                placeholder="Riwayat pembedahan sebelumnya..."
                                            />
                                        </div>
                                    </div>

                                    {/* Alergi (KF-2) */}
                                    <div className="bg-muted/30 grid grid-cols-1 items-start gap-4 rounded-lg border p-3 md:grid-cols-3">
                                        <div className="space-y-2">
                                            <Label htmlFor="allergy_status" className="flex items-center gap-1.5 font-semibold">
                                                <ShieldAlert className="h-4 w-4 text-amber-500" />
                                                Riwayat Alergi
                                            </Label>
                                            <Select
                                                value={mainForm.data.allergy_status}
                                                onValueChange={(val) => mainForm.setData('allergy_status', val)}
                                                disabled={!canManage}
                                            >
                                                <SelectTrigger id="allergy_status">
                                                    <SelectValue placeholder="Pilih Status Alergi" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {options.allergyStatuses.map((s) => (
                                                        <SelectItem key={s.value} value={s.value}>
                                                            {s.label}
                                                        </SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                            <InputError message={mainForm.errors.allergy_status} />
                                        </div>

                                        <div className="space-y-2 md:col-span-2">
                                            <Label htmlFor="allergy_detail">
                                                Detail Alergi{' '}
                                                {mainForm.data.allergy_status === 'ada' && <span className="text-red-500">* (Wajib diisi)</span>}
                                            </Label>
                                            <Input
                                                id="allergy_detail"
                                                value={mainForm.data.allergy_detail}
                                                onChange={(e) => mainForm.setData('allergy_detail', e.target.value)}
                                                disabled={!canManage || mainForm.data.allergy_status !== 'ada'}
                                                placeholder={
                                                    mainForm.data.allergy_status === 'ada'
                                                        ? 'Sebutkan alergi obat/makanan/lateks...'
                                                        : 'Tidak ada alergi'
                                                }
                                            />
                                            <InputError message={mainForm.errors.allergy_detail} />
                                        </div>
                                    </div>

                                    {/* Puasa & Penggunaan Obat */}
                                    <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                                        <div className="bg-muted/30 space-y-3 rounded-lg border p-3">
                                            <div className="flex items-center space-x-2">
                                                <Checkbox
                                                    id="fasting_status"
                                                    checked={mainForm.data.fasting_status}
                                                    onCheckedChange={(checked) => mainForm.setData('fasting_status', Boolean(checked))}
                                                    disabled={!canManage}
                                                />
                                                <Label htmlFor="fasting_status" className="cursor-pointer font-medium">
                                                    Pasien Sudah Puasa
                                                </Label>
                                            </div>
                                            {mainForm.data.fasting_status && (
                                                <div className="space-y-1">
                                                    <Label htmlFor="fasting_since" className="text-xs">
                                                        Puasa Sejak Jam Berapa <span className="text-red-500">*</span>
                                                    </Label>
                                                    <Input
                                                        id="fasting_since"
                                                        type="datetime-local"
                                                        value={mainForm.data.fasting_since}
                                                        onChange={(e) => mainForm.setData('fasting_since', e.target.value)}
                                                        disabled={!canManage}
                                                    />
                                                    <InputError message={mainForm.errors.fasting_since} />
                                                </div>
                                            )}
                                        </div>

                                        <div className="space-y-2">
                                            <Label htmlFor="medication_history">Riwayat Penggunaan Obat Rutin</Label>
                                            <Input
                                                id="medication_history"
                                                value={mainForm.data.medication_history}
                                                onChange={(e) => mainForm.setData('medication_history', e.target.value)}
                                                disabled={!canManage}
                                                placeholder="Obat antihipertensi, antikoagulan, insulin..."
                                            />
                                        </div>
                                    </div>

                                    <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                                        <div className="space-y-2">
                                            <Label htmlFor="anesthesia_history">Riwayat Anestesi Sebelumnya</Label>
                                            <Input
                                                id="anesthesia_history"
                                                value={mainForm.data.anesthesia_history}
                                                onChange={(e) => mainForm.setData('anesthesia_history', e.target.value)}
                                                disabled={!canManage}
                                                placeholder="Riwayat komplikasi atau reaksi anestesi..."
                                            />
                                        </div>
                                        <div className="space-y-2">
                                            <Label htmlFor="skin_condition">Kondisi Kulit</Label>
                                            <Input
                                                id="skin_condition"
                                                value={mainForm.data.skin_condition}
                                                onChange={(e) => mainForm.setData('skin_condition', e.target.value)}
                                                disabled={!canManage}
                                                placeholder="Kulit utuh, lesi, ruam, turgor..."
                                            />
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>

                            {/* BAGIAN C: Pemeriksaan Fisik & TTV (KF-3) */}
                            <Card>
                                <CardHeader className="pb-4">
                                    <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                        <div>
                                            <CardTitle className="flex items-center gap-2 text-base font-semibold">
                                                <HeartPulse className="h-5 w-5 text-rose-500" />
                                                C. Pemeriksaan Fisik & Tanda-Tanda Vital
                                            </CardTitle>
                                            <CardDescription>TTV pra-bedah untuk dasar pemantauan kestabilan hemodinamik.</CardDescription>
                                        </div>
                                        {(bpWarning || pulseWarning || tempWarning || rrWarning || spo2Warning) && (
                                            <Badge
                                                variant="outline"
                                                className="flex items-center gap-1 border-amber-300 bg-amber-50 text-amber-800 dark:bg-amber-950/20 dark:text-amber-300"
                                            >
                                                <AlertTriangle className="h-3.5 w-3.5" />
                                                Peringatan: TTV di luar batas normal
                                            </Badge>
                                        )}
                                    </div>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                                        <div className="space-y-1.5">
                                            <Label htmlFor="bp_systolic" className="text-xs">
                                                TD Sistolik (mmHg)
                                            </Label>
                                            <Input
                                                id="bp_systolic"
                                                type="number"
                                                value={mainForm.data.vital_signs.bp_systolic}
                                                onChange={(e) =>
                                                    mainForm.setData('vital_signs', { ...mainForm.data.vital_signs, bp_systolic: e.target.value })
                                                }
                                                disabled={!canManage}
                                                placeholder="120"
                                            />
                                            {bpWarning && <p className="text-[11px] font-medium text-amber-600">Di luar rentang 90–140</p>}
                                        </div>

                                        <div className="space-y-1.5">
                                            <Label htmlFor="bp_diastolic" className="text-xs">
                                                TD Diastolik (mmHg)
                                            </Label>
                                            <Input
                                                id="bp_diastolic"
                                                type="number"
                                                value={mainForm.data.vital_signs.bp_diastolic}
                                                onChange={(e) =>
                                                    mainForm.setData('vital_signs', { ...mainForm.data.vital_signs, bp_diastolic: e.target.value })
                                                }
                                                disabled={!canManage}
                                                placeholder="80"
                                            />
                                        </div>

                                        <div className="space-y-1.5">
                                            <Label htmlFor="pulse" className="text-xs">
                                                Nadi (x/menit)
                                            </Label>
                                            <Input
                                                id="pulse"
                                                type="number"
                                                value={mainForm.data.vital_signs.pulse}
                                                onChange={(e) =>
                                                    mainForm.setData('vital_signs', { ...mainForm.data.vital_signs, pulse: e.target.value })
                                                }
                                                disabled={!canManage}
                                                placeholder="80"
                                            />
                                            {pulseWarning && <p className="text-[11px] font-medium text-amber-600">Di luar rentang 60–100</p>}
                                        </div>

                                        <div className="space-y-1.5">
                                            <Label htmlFor="temperature" className="text-xs">
                                                Suhu (°C)
                                            </Label>
                                            <Input
                                                id="temperature"
                                                type="number"
                                                step="0.1"
                                                value={mainForm.data.vital_signs.temperature}
                                                onChange={(e) =>
                                                    mainForm.setData('vital_signs', { ...mainForm.data.vital_signs, temperature: e.target.value })
                                                }
                                                disabled={!canManage}
                                                placeholder="36.5"
                                            />
                                            {tempWarning && <p className="text-[11px] font-medium text-amber-600">Di luar 36.0–37.5°C</p>}
                                        </div>

                                        <div className="space-y-1.5">
                                            <Label htmlFor="rr" className="text-xs">
                                                Pernapasan / RR (x/mnt)
                                            </Label>
                                            <Input
                                                id="rr"
                                                type="number"
                                                value={mainForm.data.vital_signs.respiratory_rate}
                                                onChange={(e) =>
                                                    mainForm.setData('vital_signs', {
                                                        ...mainForm.data.vital_signs,
                                                        respiratory_rate: e.target.value,
                                                    })
                                                }
                                                disabled={!canManage}
                                                placeholder="18"
                                            />
                                            {rrWarning && <p className="text-[11px] font-medium text-amber-600">Di luar rentang 12–22</p>}
                                        </div>

                                        <div className="space-y-1.5">
                                            <Label htmlFor="spo2" className="text-xs">
                                                SpO2 (%)
                                            </Label>
                                            <Input
                                                id="spo2"
                                                type="number"
                                                value={mainForm.data.vital_signs.spo2}
                                                onChange={(e) =>
                                                    mainForm.setData('vital_signs', { ...mainForm.data.vital_signs, spo2: e.target.value })
                                                }
                                                disabled={!canManage}
                                                placeholder="98"
                                            />
                                            {spo2Warning && <p className="text-[11px] font-medium text-amber-600">SpO2 &lt; 95%</p>}
                                        </div>

                                        <div className="space-y-1.5">
                                            <Label htmlFor="weight_kg" className="text-xs">
                                                Berat Badan (kg)
                                            </Label>
                                            <Input
                                                id="weight_kg"
                                                type="number"
                                                step="0.1"
                                                value={mainForm.data.vital_signs.weight_kg}
                                                onChange={(e) =>
                                                    mainForm.setData('vital_signs', { ...mainForm.data.vital_signs, weight_kg: e.target.value })
                                                }
                                                disabled={!canManage}
                                                placeholder="60"
                                            />
                                        </div>

                                        <div className="space-y-1.5">
                                            <Label htmlFor="height_cm" className="text-xs">
                                                Tinggi Badan (cm)
                                            </Label>
                                            <Input
                                                id="height_cm"
                                                type="number"
                                                step="0.1"
                                                value={mainForm.data.vital_signs.height_cm}
                                                onChange={(e) =>
                                                    mainForm.setData('vital_signs', { ...mainForm.data.vital_signs, height_cm: e.target.value })
                                                }
                                                disabled={!canManage}
                                                placeholder="165"
                                            />
                                        </div>
                                    </div>

                                    <div className="grid grid-cols-1 gap-4 pt-2 md:grid-cols-2">
                                        <div className="space-y-2">
                                            <Label htmlFor="consciousness">Tingkat Kesadaran</Label>
                                            <Select
                                                value={mainForm.data.vital_signs.consciousness}
                                                onValueChange={(val) =>
                                                    mainForm.setData('vital_signs', { ...mainForm.data.vital_signs, consciousness: val })
                                                }
                                                disabled={!canManage}
                                            >
                                                <SelectTrigger id="consciousness">
                                                    <SelectValue placeholder="Pilih Kesadaran" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {options.consciousnessList.map((c) => (
                                                        <SelectItem key={c.value} value={c.value}>
                                                            {c.label}
                                                        </SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                        </div>

                                        <div className="space-y-2">
                                            <Label htmlFor="vital_recorded_at">Waktu Pengukuran TTV</Label>
                                            <Input
                                                id="vital_recorded_at"
                                                type="datetime-local"
                                                value={mainForm.data.vital_signs.recorded_at}
                                                onChange={(e) =>
                                                    mainForm.setData('vital_signs', { ...mainForm.data.vital_signs, recorded_at: e.target.value })
                                                }
                                                disabled={!canManage}
                                            />
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>

                            {/* BAGIAN D: Pemeriksaan Penunjang (KF-19) */}
                            <Card>
                                <CardHeader className="flex flex-row items-center justify-between pb-4">
                                    <div>
                                        <CardTitle className="text-base font-semibold">D. Pemeriksaan Penunjang</CardTitle>
                                        <CardDescription>Laboratorium, radiologi, dan pemeriksaan diagnostik lainnya.</CardDescription>
                                    </div>
                                    {canManage && (
                                        <Button type="button" variant="outline" size="sm" onClick={addExamRow} className="gap-1.5">
                                            <Plus className="h-3.5 w-3.5" />
                                            Tambah Pemeriksaan
                                        </Button>
                                    )}
                                </CardHeader>
                                <CardContent>
                                    <div className="space-y-3">
                                        {exams.map((row, index) => (
                                            <div
                                                key={index}
                                                className="bg-muted/20 grid grid-cols-1 items-center gap-2 rounded-lg border p-2 sm:grid-cols-12"
                                            >
                                                <div className="sm:col-span-4">
                                                    <Select
                                                        value={row.exam_type}
                                                        onValueChange={(val) => updateExamRow(index, 'exam_type', val)}
                                                        disabled={!canManage}
                                                    >
                                                        <SelectTrigger className="h-9">
                                                            <SelectValue placeholder="Jenis Pemeriksaan" />
                                                        </SelectTrigger>
                                                        <SelectContent>
                                                            {options.examTypes.map((t) => (
                                                                <SelectItem key={t.value} value={t.value}>
                                                                    {t.label}
                                                                </SelectItem>
                                                            ))}
                                                        </SelectContent>
                                                    </Select>
                                                </div>
                                                <div className="sm:col-span-5">
                                                    <Input
                                                        className="h-9"
                                                        placeholder="Hasil pemeriksaan..."
                                                        value={row.result}
                                                        onChange={(e) => updateExamRow(index, 'result', e.target.value)}
                                                        disabled={!canManage}
                                                    />
                                                </div>
                                                <div className="sm:col-span-2">
                                                    <Input
                                                        className="h-9"
                                                        type="date"
                                                        value={row.exam_date}
                                                        onChange={(e) => updateExamRow(index, 'exam_date', e.target.value)}
                                                        disabled={!canManage}
                                                    />
                                                </div>
                                                <div className="flex justify-end sm:col-span-1">
                                                    {canManage && exams.length > 1 && (
                                                        <Button
                                                            type="button"
                                                            variant="ghost"
                                                            size="icon"
                                                            aria-label="Hapus baris pemeriksaan penunjang"
                                                            onClick={() => removeExamRow(index)}
                                                            className="text-destructive"
                                                        >
                                                            <Trash2 className="h-4 w-4" />
                                                        </Button>
                                                    )}
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                </CardContent>
                            </Card>

                            {/* BAGIAN E: Checklist Persiapan & Asesmen Psikologis (KF-4 & KF-21) */}
                            <Card>
                                <CardHeader className="pb-4">
                                    <CardTitle className="text-base font-semibold">E. Checklist Persiapan Pra-Operasi & Asesmen Psikologis</CardTitle>
                                    <CardDescription>
                                        Semua 10 item checklist persiapan wajib lengkap agar kesiapan operasi dapat dinyatakan Siap.
                                    </CardDescription>
                                </CardHeader>
                                <CardContent className="space-y-6">
                                    <div className="bg-muted/20 space-y-3 rounded-xl border p-4">
                                        <Label className="text-sm font-semibold">10 Item Verifikasi Kelengkapan Persiapan Pasien:</Label>
                                        <div className="grid grid-cols-1 gap-3 pt-1 md:grid-cols-2">
                                            {[
                                                { key: 'identity_verified', label: '1. Identitas pasien terverifikasi' },
                                                { key: 'informed_consent_surgery', label: '2. Informed consent bedah / operasi lengkap' },
                                                { key: 'anesthesia_consent', label: '3. Persetujuan tindakan anestesi lengkap' },
                                                { key: 'fasting_verified', label: '4. Status puasa terverifikasi' },
                                                { key: 'allergy_assessed', label: '5. Alergi telah dikaji' },
                                                { key: 'lab_available', label: '6. Hasil laboratorium tersedia' },
                                                { key: 'iv_access', label: '7. Akses intravena (IV line) terpasang paten' },
                                                { key: 'operation_area_prepared', label: '8. Persiapan area operasi (kebersihan/cukur bila perlu)' },
                                                { key: 'jewelry_removed', label: '9. Pelepasan perhiasan, gigi palsu, dan benda pengganggu' },
                                            ].map((item) => (
                                                <div key={item.key} className="flex items-center space-x-2">
                                                    <Checkbox
                                                        id={item.key}
                                                        checked={Boolean(
                                                            mainForm.data.preparation[item.key as keyof typeof mainForm.data.preparation],
                                                        )}
                                                        onCheckedChange={(checked) => {
                                                            mainForm.setData('preparation', {
                                                                ...mainForm.data.preparation,
                                                                [item.key]: Boolean(checked),
                                                            });
                                                        }}
                                                        disabled={!canManage}
                                                    />
                                                    <Label htmlFor={item.key} className="cursor-pointer text-xs leading-tight font-normal">
                                                        {item.label}
                                                    </Label>
                                                </div>
                                            ))}

                                            {/* Item 10: Tanda Lokasi Operasi (opsional N/A) */}
                                            <div className="col-span-1 flex items-center space-x-2 border-t pt-2 md:col-span-2">
                                                <Checkbox
                                                    id="site_marked"
                                                    checked={mainForm.data.preparation.site_marked === true}
                                                    onCheckedChange={(checked) => {
                                                        mainForm.setData('preparation', {
                                                            ...mainForm.data.preparation,
                                                            site_marked: checked ? true : false,
                                                        });
                                                    }}
                                                    disabled={!canManage}
                                                />
                                                <Label htmlFor="site_marked" className="cursor-pointer text-xs leading-tight font-normal">
                                                    10. Tanda lokasi operasi (site marking) telah dibuat dan terverifikasi
                                                </Label>
                                            </div>
                                        </div>
                                    </div>

                                    {/* Asesmen Psikologis (KF-21) */}
                                    <div className="space-y-4 pt-2">
                                        <Label className="text-sm font-semibold">Asesmen Psikologis Pasien:</Label>
                                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                                            <div className="space-y-2">
                                                <Label htmlFor="anxiety_level">Tingkat Kecemasan</Label>
                                                <Select
                                                    value={mainForm.data.anxiety_level}
                                                    onValueChange={(val) => mainForm.setData('anxiety_level', val)}
                                                    disabled={!canManage}
                                                >
                                                    <SelectTrigger id="anxiety_level">
                                                        <SelectValue placeholder="Pilih Kecemasan" />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        {options.anxietyLevels.map((a) => (
                                                            <SelectItem key={a.value} value={a.value}>
                                                                {a.label}
                                                            </SelectItem>
                                                        ))}
                                                    </SelectContent>
                                                </Select>
                                            </div>

                                            <div className="space-y-2">
                                                <Label htmlFor="surgery_knowledge">Pengetahuan Operasi</Label>
                                                <Input
                                                    id="surgery_knowledge"
                                                    value={mainForm.data.surgery_knowledge}
                                                    onChange={(e) => mainForm.setData('surgery_knowledge', e.target.value)}
                                                    disabled={!canManage}
                                                    placeholder="Pemahaman tentang operasi..."
                                                />
                                            </div>

                                            <div className="space-y-2">
                                                <Label htmlFor="family_support">Dukungan Keluarga</Label>
                                                <Input
                                                    id="family_support"
                                                    value={mainForm.data.family_support}
                                                    onChange={(e) => mainForm.setData('family_support', e.target.value)}
                                                    disabled={!canManage}
                                                    placeholder="Pendampingan keluarga..."
                                                />
                                            </div>

                                            <div className="space-y-2">
                                                <Label htmlFor="mental_readiness">Kesiapan Mental</Label>
                                                <Input
                                                    id="mental_readiness"
                                                    value={mainForm.data.mental_readiness}
                                                    onChange={(e) => mainForm.setData('mental_readiness', e.target.value)}
                                                    disabled={!canManage}
                                                    placeholder="Sikap mental menghadapi bedah..."
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>

                            {/* BAGIAN F: Diagnosis Keperawatan, Implementasi, dan Evaluasi (KF-22 & KF-35) */}
                            <Card>
                                <CardHeader className="flex flex-row items-center justify-between pb-4">
                                    <div>
                                        <CardTitle className="text-base font-semibold">
                                            F. Diagnosis Keperawatan (SDKI), Luaran (SLKI) & Intervensi (SIKI)
                                        </CardTitle>
                                        <CardDescription>Checklist intervensi dan kriteria hasil standar pra-operasi.</CardDescription>
                                    </div>
                                    {canManage && (
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="sm"
                                            onClick={() => setAddDiagDialogOpen(true)}
                                            className="gap-1.5"
                                        >
                                            <Plus className="h-3.5 w-3.5" />
                                            Tambah Diagnosis SDKI
                                        </Button>
                                    )}
                                </CardHeader>
                                <CardContent className="space-y-6">
                                    {/* Kartu Diagnosis Terpilih */}
                                    {caseNursingDiagnoses.length === 0 ? (
                                        <div className="text-muted-foreground rounded-lg border border-dashed p-4 text-center text-sm">
                                            Belum ada diagnosis keperawatan pra-operasi yang ditambahkan. Klik tombol "Tambah Diagnosis SDKI" di atas.
                                        </div>
                                    ) : (
                                        <div className="space-y-4">
                                            {caseNursingDiagnoses.map((diag) => (
                                                <div key={diag.id} className="bg-card space-y-4 rounded-xl border p-4 shadow-xs">
                                                    <div className="flex items-center justify-between border-b pb-2">
                                                        <div>
                                                            <h4 className="text-foreground flex items-center gap-2 text-sm font-semibold">
                                                                <Badge variant="secondary">{diag.code || 'SDKI'}</Badge>
                                                                {diag.name}
                                                            </h4>
                                                            {diag.notes && <p className="text-muted-foreground mt-0.5 text-xs">{diag.notes}</p>}
                                                        </div>
                                                        {canManage && (
                                                            <Button
                                                                type="button"
                                                                variant="ghost"
                                                                size="sm"
                                                                onClick={() => handleDeleteDiagnosis(diag.id)}
                                                                className="text-destructive hover:bg-destructive/10 h-8"
                                                            >
                                                                Hapus
                                                            </Button>
                                                        )}
                                                    </div>

                                                    {/* Kriteria Luaran SLKI */}
                                                    {diag.criteria.length > 0 && (
                                                        <div className="space-y-2">
                                                            <Label className="text-primary text-xs font-semibold">
                                                                Kriteria Hasil Luaran (SLKI):
                                                            </Label>
                                                            <div className="grid grid-cols-1 gap-2 text-xs sm:grid-cols-2">
                                                                {diag.criteria.map((c) => (
                                                                    <div
                                                                        key={c.id}
                                                                        className="bg-muted/20 flex items-center justify-between rounded border p-2"
                                                                    >
                                                                        <span>{c.text}</span>
                                                                        <Badge variant="outline" className="text-[10px]">
                                                                            Target: {c.target}
                                                                        </Badge>
                                                                    </div>
                                                                ))}
                                                            </div>
                                                        </div>
                                                    )}

                                                    {/* Checklist Intervensi SIKI */}
                                                    {diag.interventions.length > 0 && (
                                                        <div className="space-y-3 pt-1">
                                                            <Label className="text-primary text-xs font-semibold">
                                                                Intervensi Keperawatan (SIKI):
                                                            </Label>
                                                            {diag.interventions.map((intv) => (
                                                                <div key={intv.id} className="space-y-2">
                                                                    <p className="text-foreground text-xs font-medium">{intv.name}</p>
                                                                    <div className="grid grid-cols-1 gap-2 md:grid-cols-2">
                                                                        {intv.items.map((item) => (
                                                                            <div
                                                                                key={item.id || item.siki_item_id}
                                                                                className={`flex items-start gap-2.5 rounded-lg border p-2.5 text-xs transition-colors ${
                                                                                    item.is_done ? 'bg-primary/5 border-primary/30' : 'bg-muted/10'
                                                                                }`}
                                                                            >
                                                                                <Checkbox
                                                                                    id={`siki-${item.id || item.siki_item_id}`}
                                                                                    checked={item.is_done}
                                                                                    onCheckedChange={() => handleToggleIntervention(item.id)}
                                                                                    disabled={!canManage || !item.id}
                                                                                    className="mt-0.5"
                                                                                />
                                                                                <div
                                                                                    className="cursor-pointer space-y-0.5 leading-snug"
                                                                                    onClick={() => item.id && handleToggleIntervention(item.id)}
                                                                                >
                                                                                    <span className="text-muted-foreground mr-1.5 text-[10px] font-semibold tracking-wider uppercase">
                                                                                        [{item.section_label}]
                                                                                    </span>
                                                                                    <span
                                                                                        className={
                                                                                            item.is_done
                                                                                                ? 'text-foreground font-medium'
                                                                                                : 'text-muted-foreground'
                                                                                        }
                                                                                    >
                                                                                        {item.text}
                                                                                    </span>
                                                                                    {item.done_at && (
                                                                                        <p className="text-[10px] font-normal text-emerald-600 dark:text-emerald-400">
                                                                                            ✔ Dilakukan {item.done_at}
                                                                                        </p>
                                                                                    )}
                                                                                </div>
                                                                            </div>
                                                                        ))}
                                                                    </div>
                                                                </div>
                                                            ))}
                                                        </div>
                                                    )}
                                                </div>
                                            ))}
                                        </div>
                                    )}

                                    {/* Form Implementasi & Evaluasi */}
                                    <div className="space-y-4 border-t pt-4">
                                        <Label className="text-sm font-semibold">Implementasi & Evaluasi Keperawatan:</Label>
                                        <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                                            <div className="space-y-2">
                                                <Label htmlFor="imp_actions">Tindakan yang Dilakukan</Label>
                                                <Input
                                                    id="imp_actions"
                                                    value={mainForm.data.implementation_actions}
                                                    onChange={(e) => mainForm.setData('implementation_actions', e.target.value)}
                                                    disabled={!canManage}
                                                    placeholder="Tindakan keperawatan spesifik..."
                                                />
                                            </div>
                                            <div className="space-y-2">
                                                <Label htmlFor="imp_education">Edukasi Pasien</Label>
                                                <Input
                                                    id="imp_education"
                                                    value={mainForm.data.implementation_education}
                                                    onChange={(e) => mainForm.setData('implementation_education', e.target.value)}
                                                    disabled={!canManage}
                                                    placeholder="Edukasi yang diberikan..."
                                                />
                                            </div>
                                        </div>

                                        <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                                            <div className="space-y-2">
                                                <Label htmlFor="imp_monitoring">Monitoring Tanda Vital</Label>
                                                <Input
                                                    id="imp_monitoring"
                                                    value={mainForm.data.implementation_monitoring}
                                                    onChange={(e) => mainForm.setData('implementation_monitoring', e.target.value)}
                                                    disabled={!canManage}
                                                    placeholder="Hasil monitoring..."
                                                />
                                            </div>
                                            <div className="space-y-2">
                                                <Label htmlFor="imp_response">Respons Pasien</Label>
                                                <Input
                                                    id="imp_response"
                                                    value={mainForm.data.implementation_response}
                                                    onChange={(e) => mainForm.setData('implementation_response', e.target.value)}
                                                    disabled={!canManage}
                                                    placeholder="Respons pasien..."
                                                />
                                            </div>
                                        </div>

                                        <div className="grid grid-cols-1 gap-4 pt-2 md:grid-cols-3">
                                            <div className="space-y-2">
                                                <Label htmlFor="eval_condition">Kondisi Pasien</Label>
                                                <Input
                                                    id="eval_condition"
                                                    value={mainForm.data.evaluation_condition}
                                                    onChange={(e) => mainForm.setData('evaluation_condition', e.target.value)}
                                                    disabled={!canManage}
                                                    placeholder="Kondisi umum..."
                                                />
                                            </div>
                                            <div className="space-y-2">
                                                <Label htmlFor="eval_comp">Kelengkapan Persiapan</Label>
                                                <Input
                                                    id="eval_comp"
                                                    value={mainForm.data.evaluation_completeness}
                                                    onChange={(e) => mainForm.setData('evaluation_completeness', e.target.value)}
                                                    disabled={!canManage}
                                                    placeholder="Kelengkapan berkas/alat..."
                                                />
                                            </div>
                                            <div className="space-y-2">
                                                <Label htmlFor="readiness_status">
                                                    Status Kesiapan Operasi <span className="text-red-500">*</span>
                                                </Label>
                                                <Select
                                                    value={mainForm.data.readiness_status}
                                                    onValueChange={(val) => mainForm.setData('readiness_status', val)}
                                                    disabled={!canManage}
                                                >
                                                    <SelectTrigger id="readiness_status">
                                                        <SelectValue placeholder="Pilih Kesiapan" />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        {options.readinessStatuses.map((r) => (
                                                            <SelectItem key={r.value} value={r.value}>
                                                                {r.label}
                                                            </SelectItem>
                                                        ))}
                                                    </SelectContent>
                                                </Select>
                                                <InputError message={mainForm.errors.readiness_status} />
                                            </div>
                                        </div>

                                        <div className="space-y-2">
                                            <Label htmlFor="eval_notes">Catatan Tambahan Evaluasi</Label>
                                            <Input
                                                id="eval_notes"
                                                value={mainForm.data.evaluation_notes}
                                                onChange={(e) => mainForm.setData('evaluation_notes', e.target.value)}
                                                disabled={!canManage}
                                                placeholder="Catatan khusus kesiapan..."
                                            />
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>

                            <PreopNeuroDetailsCard details={mainForm.data.clinical_details} onChange={updateClinicalField} disabled={!canManage} />
                            <PreopOrthoDetailsCard details={mainForm.data.clinical_details} onChange={updateClinicalField} disabled={!canManage} />

                            <PreopAnesthesiaCard details={mainForm.data.clinical_details} onChange={updateClinicalField} disabled={!canManage} />
                        </>
                    )}

                    {/* Tombol Simpan Bawah Form */}
                    {canManage && (
                        <div className="flex justify-end gap-3 pt-2">
                            <Button type="submit" size="lg" disabled={mainForm.processing} className="gap-2 px-8">
                                <Save className="h-4 w-4" />
                                {mainForm.processing ? 'Menyimpan...' : 'Simpan Semua Bagian Pra-Operasi'}
                            </Button>
                        </div>
                    )}
                </form>
            </div>

            {/* Dialog Pengesahan Perawat (dengan konfirmasi sandi) */}
            <Dialog open={signDialogOpen} onOpenChange={setSignDialogOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2">
                            <FileCheck className="text-primary h-5 w-5" />
                            Pengesahan Pra-Operasi oleh Perawat
                        </DialogTitle>
                        <DialogDescription>
                            Tindakan ini menyatakan bahwa asesmen dan persiapan pra-operasi telah diverifikasi dengan benar. Masukkan kata sandi akun
                            Anda untuk membubuhkan tanda tangan elektronik.
                        </DialogDescription>
                    </DialogHeader>

                    <form onSubmit={handleSignSubmit} className="space-y-4 py-2">
                        <div className="space-y-2">
                            <Label htmlFor="sign_password">Konfirmasi Kata Sandi Akun</Label>
                            <Input
                                id="sign_password"
                                type="password"
                                value={signForm.data.password}
                                onChange={(e) => signForm.setData('password', e.target.value)}
                                placeholder="Masukkan kata sandi login Anda"
                                autoFocus
                            />
                            <InputError message={signForm.errors.password} />
                        </div>

                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setSignDialogOpen(false)}>
                                Batal
                            </Button>
                            <Button type="submit" disabled={signForm.processing}>
                                {signForm.processing ? 'Memproses...' : 'Bubuhkan Pengesahan'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Dialog Verifikasi Dokter (dengan konfirmasi sandi) */}
            <Dialog open={verifyDialogOpen} onOpenChange={setVerifyDialogOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2">
                            <UserCheck className="text-primary h-5 w-5" />
                            Verifikasi Pra-Operasi oleh Dokter
                        </DialogTitle>
                        <DialogDescription>
                            Dokter operator atau dokter anestesi memverifikasi rencana tindakan operasi dan kelayakan pasien. Masukkan kata sandi akun
                            Anda untuk menandatangani verifikasi.
                        </DialogDescription>
                    </DialogHeader>

                    <form onSubmit={handleVerifySubmit} className="space-y-4 py-2">
                        <div className="space-y-2">
                            <Label htmlFor="verify_password">Konfirmasi Kata Sandi Akun</Label>
                            <Input
                                id="verify_password"
                                type="password"
                                value={verifyForm.data.password}
                                onChange={(e) => verifyForm.setData('password', e.target.value)}
                                placeholder="Masukkan kata sandi login Anda"
                                autoFocus
                            />
                            <InputError message={verifyForm.errors.password} />
                        </div>

                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setVerifyDialogOpen(false)}>
                                Batal
                            </Button>
                            <Button type="submit" disabled={verifyForm.processing}>
                                {verifyForm.processing ? 'Memproses...' : 'Bubuhkan Verifikasi'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Dialog Tambah Diagnosis Keperawatan SDKI (disembunyikan untuk profil SKPL EDH & Ortopedi) */}
            {activeSurgeryProfile === null && (
                <Dialog open={addDiagDialogOpen} onOpenChange={setAddDiagDialogOpen}>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Tambah Diagnosis Keperawatan Pra-Operasi (SDKI)</DialogTitle>
                            <DialogDescription>
                                Pilih diagnosis standar dari katalog SDKI pra-bedah untuk menampilkan kriteria hasil SLKI dan intervensi SIKI
                                otomatis.
                            </DialogDescription>
                        </DialogHeader>

                        <form onSubmit={handleAddDiagSubmit} className="space-y-4 py-2">
                            <div className="space-y-2">
                                <Label htmlFor="add_diag_id">Pilih Diagnosis SDKI</Label>
                                <Select
                                    value={diagForm.data.nursing_diagnosis_id}
                                    onValueChange={(val) => diagForm.setData('nursing_diagnosis_id', val)}
                                >
                                    <SelectTrigger id="add_diag_id">
                                        <SelectValue placeholder="Pilih Diagnosis SDKI" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {options.masterNursingDiagnoses.map((d) => (
                                            <SelectItem key={d.id} value={String(d.id)}>
                                                [{d.code}] {d.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={diagForm.errors.nursing_diagnosis_id} />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="custom_text">Atau Tulis Teks Diagnosis Lainnya</Label>
                                <Input
                                    id="custom_text"
                                    value={diagForm.data.custom_text}
                                    onChange={(e) => diagForm.setData('custom_text', e.target.value)}
                                    placeholder="Contoh: Risiko ketidakseimbangan elektrolit..."
                                />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="diag_notes">Catatan Tambahan (Opsional)</Label>
                                <Input
                                    id="diag_notes"
                                    value={diagForm.data.notes}
                                    onChange={(e) => diagForm.setData('notes', e.target.value)}
                                    placeholder="Catatan kondisi khusus..."
                                />
                            </div>

                            <DialogFooter>
                                <Button type="button" variant="outline" onClick={() => setAddDiagDialogOpen(false)}>
                                    Batal
                                </Button>
                                <Button type="submit" disabled={diagForm.processing}>
                                    {diagForm.processing ? 'Menyimpan...' : 'Tambahkan Diagnosis'}
                                </Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            )}
        </CaseLayout>
    );
}

export type PreopClinicalDetails = {
    incident: {
        injury_cause: string;
        incident_datetime: string;
        incident_location: string;
        pain_location: string;
        headache_history: string;
        nausea_vomiting: string;
        loss_of_consciousness: string;
        seizure_history: string;
        anticoagulant_use: string;
    };
    neurological: {
        consciousness_level: string;
        gcs_e: string;
        gcs_v: string;
        gcs_m: string;
        pupil_size_right: string;
        pupil_size_left: string;
        pupil_light_reaction: string;
        motor_right: string;
        motor_left: string;
        sensory_response: string;
        seizure_status: string;
        neuro_change_during_monitoring: string;
    };
    extremity_physical: {
        fracture_location: string;
        fracture_side: string;
        fracture_type: string;
        fracture_pattern: string;
        skin_condition: string;
        open_wound: string;
        bleeding: string;
        swelling: string;
        deformity: string;
        pain_nrs: string;
        extremity_movement: string;
    };
    neurovascular: {
        foot_skin_color: string;
        foot_skin_temp: string;
        crt: string;
        peripheral_pulse: string;
        foot_sensation: string;
        toe_movement: string;
        tingling: string;
        numbness: string;
    };
    vital_extra: {
        gds: string;
    };
    supporting_detail: {
        ct_scan_result: string;
        hematoma_location: string;
        hematoma_side: string;
        hematoma_thickness: string;
        midline_shift: string;
        skull_fracture: string;
        associated_bleeding: string;
        xray_femur_result: string;
        xray_location_side: string;
        xray_pattern: string;
        ct_scan_note: string;
        hb: string;
        ht: string;
        leukosit: string;
        trombosit: string;
        blood_type: string;
        pt_inr: string;
        aptt: string;
        electrolytes: string;
        blood_glucose: string;
        renal_function: string;
    };
    prep_extra: {
        decision_time: string;
        iv_fluid_detail: string;
        blood_prep: string;
        antibiotic_instruction: string;
        consent_signer: string;
        consent_relation: string;
        emergency_consent_doc: string;
        implant_instrument_prep: string;
    };
    anesthesia_pre: {
        airway_exam: string;
        cardiopulmonary: string;
        asa_class: string;
        planned_anesthesia: string;
    };
    anesthesia_exec: {
        anesthesia_method: string;
        anesthesia_start_time: string;
        anesthesia_end_time: string;
        ett_type_size: string;
        ett_depth_cm: string;
        airway_aid: string;
        ventilator_setting: string;
        medications_summary: string;
        medication_dose: string;
        medication_time: string;
        fluids_given: string;
        vitals_bp?: string;
        vitals_pulse?: string;
        vitals_rr?: string;
        vitals_spo2?: string;
        vitals_monitoring_note: string;
        complications: string;
    };
};

export type UpdateClinicalFieldFn = <S extends keyof PreopClinicalDetails, K extends keyof PreopClinicalDetails[S]>(
    section: S,
    field: K,
    value: string,
) => void;

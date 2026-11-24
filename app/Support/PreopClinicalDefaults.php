<?php

namespace App\Support;

class PreopClinicalDefaults
{
    /**
     * @param  array<string, mixed>|null  $stored
     * @return array<string, mixed>
     */
    public static function merge(?array $stored): array
    {
        $defaults = [
            'incident' => [
                'injury_cause' => '',
                'incident_datetime' => '',
                'incident_location' => '',
                'pain_location' => '',
                'headache_history' => '',
                'nausea_vomiting' => '',
                'loss_of_consciousness' => '',
                'seizure_history' => '',
                'anticoagulant_use' => '',
            ],
            'neurological' => [
                'consciousness_level' => '',
                'gcs_e' => '',
                'gcs_v' => '',
                'gcs_m' => '',
                'pupil_size_right' => '',
                'pupil_size_left' => '',
                'pupil_light_reaction' => '',
                'motor_right' => '',
                'motor_left' => '',
                'sensory_response' => '',
                'seizure_status' => '',
                'neuro_change_during_monitoring' => '',
            ],
            'extremity_physical' => [
                'fracture_location' => '',
                'fracture_side' => '',
                'fracture_type' => '',
                'fracture_pattern' => '',
                'skin_condition' => '',
                'open_wound' => '',
                'bleeding' => '',
                'swelling' => '',
                'deformity' => '',
                'pain_nrs' => '',
                'extremity_movement' => '',
            ],
            'neurovascular' => [
                'foot_skin_color' => '',
                'foot_skin_temp' => '',
                'crt' => '',
                'peripheral_pulse' => '',
                'foot_sensation' => '',
                'toe_movement' => '',
                'tingling' => '',
                'numbness' => '',
            ],
            'vital_extra' => [
                'gds' => '',
            ],
            'supporting_detail' => [
                'ct_scan_result' => '',
                'hematoma_location' => '',
                'hematoma_side' => '',
                'hematoma_thickness' => '',
                'midline_shift' => '',
                'skull_fracture' => '',
                'associated_bleeding' => '',
                'xray_femur_result' => '',
                'xray_location_side' => '',
                'xray_pattern' => '',
                'ct_scan_note' => '',
                'hb' => '',
                'ht' => '',
                'leukosit' => '',
                'trombosit' => '',
                'blood_type' => '',
                'pt_inr' => '',
                'aptt' => '',
                'electrolytes' => '',
                'blood_glucose' => '',
                'renal_function' => '',
            ],
            'prep_extra' => [
                'decision_time' => '',
                'iv_fluid_detail' => '',
                'blood_prep' => '',
                'antibiotic_instruction' => '',
                'consent_signer' => '',
                'consent_relation' => '',
                'emergency_consent_doc' => '',
                'implant_instrument_prep' => '',
            ],
            'anesthesia_pre' => [
                'airway_exam' => '',
                'cardiopulmonary' => '',
                'asa_class' => '',
                'planned_anesthesia' => '',
            ],
            'anesthesia_exec' => [
                'anesthesia_method' => '',
                'anesthesia_start_time' => '',
                'anesthesia_end_time' => '',
                'ett_type_size' => '',
                'ett_depth_cm' => '',
                'airway_aid' => '',
                'ventilator_setting' => '',
                'medications_summary' => '',
                'medication_dose' => '',
                'medication_time' => '',
                'fluids_given' => '',
                'vitals_monitoring_note' => '',
                'complications' => '',
            ],
        ];

        $stored = is_array($stored) ? $stored : [];

        $merged = [];
        foreach ($defaults as $section => $fields) {
            $sectionInput = is_array($stored[$section] ?? null) ? $stored[$section] : [];
            $merged[$section] = [];
            foreach ($fields as $key => $defaultValue) {
                $val = $sectionInput[$key] ?? $defaultValue;
                $merged[$section][$key] = is_scalar($val) ? (string) $val : $defaultValue;
            }
        }

        return $merged;
    }

    /**
     * @param  array<string, mixed>|null  $stored
     * @return array<string, mixed>
     */
    public static function mergeIntraopProcedure(?array $stored): array
    {
        $defaults = [
            'incision_location_side' => '',
            'craniotomy_location' => '',
            'fracture_location' => '',
            'bone_tissue_condition' => '',
            'hematoma_volume_condition' => '',
            'craniotomy_opening_process' => '',
            'hematoma_evacuation_process' => '',
            'hemostasis_action' => '',
            'brain_tissue_condition' => '',
            'bone_flap_management' => '',
            'reduction_type' => '',
            'reduction_process' => '',
            'fixation_method' => '',
            'implant_placement_notes' => '',
            'fluoroscopy_check' => '',
            'drain_placement' => '',
            'wound_closure' => '',
            'postop_wound_condition' => '',
            'blood_transfusion_given' => false,
            'blood_product_type_amount' => '',
            'cautery_usage' => '',
            'equipment_usage_notes' => '',
        ];

        $stored = is_array($stored) ? $stored : [];
        $result = [];
        foreach ($defaults as $key => $defaultValue) {
            $val = $stored[$key] ?? $defaultValue;
            if (is_bool($defaultValue)) {
                $result[$key] = (bool) $val;
            } else {
                $result[$key] = is_scalar($val) ? (string) $val : '';
            }
        }

        return $result;
    }

    /**
     * @param  array<string, mixed>|null  $stored
     * @return array<string, string>
     */
    public static function mergePostopClinical(?array $stored): array
    {
        $defaults = [
            'pupil_size_right' => '',
            'pupil_size_left' => '',
            'pupil_light_reaction' => '',
            'extremity_motor' => '',
            'foot_skin_color' => '',
            'foot_skin_temp' => '',
            'crt' => '',
            'peripheral_pulse' => '',
            'foot_sensation' => '',
            'toe_movement' => '',
            'tingling_numbness' => '',
            'anesthesia_recovery_score' => '',
            'medications_given' => '',
            'doctor_postop_instructions' => '',
            'neuro_followup_monitoring' => '',
            'mobilization_instruction' => '',
            'weight_bearing_instruction' => '',
            'splint_immobilization' => '',
            'radiology_plan' => '',
        ];

        $stored = is_array($stored) ? $stored : [];
        $result = [];
        foreach ($defaults as $key => $defaultValue) {
            $val = $stored[$key] ?? $defaultValue;
            $result[$key] = is_scalar($val) ? (string) $val : '';
        }

        return $result;
    }

    /**
     * @param  array<string, mixed>|null  $stored
     * @return array<string, string>
     */
    public static function mergePostopFollowup(?array $stored): array
    {
        $defaults = [
            'medications_given' => '',
            'iv_fluid_plan' => '',
            'doctor_postop_instructions' => '',
            'neuro_followup_monitoring' => '',
            'mobilization_instruction' => '',
            'weight_bearing_instruction' => '',
            'splint_immobilization' => '',
            'radiology_plan' => '',
        ];

        $stored = is_array($stored) ? $stored : [];
        $result = [];
        foreach ($defaults as $key => $defaultValue) {
            $val = $stored[$key] ?? $defaultValue;
            $result[$key] = is_scalar($val) ? (string) $val : '';
        }

        return $result;
    }
}

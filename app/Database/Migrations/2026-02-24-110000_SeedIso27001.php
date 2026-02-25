<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Seeds the ISO 27001:2022 standard catalogue.
 * Structure: standard → standard_version → domains → clauses → controls
 *
 * ISO 27001:2022 has:
 *  - 4 organizational clauses (4-10) — not controls per se
 *  - Annex A: 4 themes → 11 categories → 93 controls
 *
 * We model the Annex A as:
 *   domain  = Theme      (e.g. A.5 Organizational controls)
 *   clause  = Category   (e.g. A.5.1 Policies for information security)
 *   control = Control    (e.g. A.5.1.1 Policies for information security)
 */
class SeedIso27001 extends Migration
{
    public function up()
    {
        $now = date('Y-m-d H:i:s');

        // ---- Standard -------------------------------------------------------
        $this->db->table('standards')->insert([
            'code'        => 'ISO27001',
            'name'        => 'ISO/IEC 27001',
            'description' => 'Information security management systems — Requirements',
            'created_at'  => $now,
            'updated_at'  => $now,
        ]);
        $standardId = $this->db->insertID();

        // ---- Standard version -----------------------------------------------
        $this->db->table('standard_versions')->insert([
            'standard_id'  => $standardId,
            'version'      => '2022',
            'title'        => 'ISO/IEC 27001:2022',
            'is_active'    => 1,
            'published_at' => '2022-10-25 00:00:00',
            'created_at'   => $now,
            'updated_at'   => $now,
        ]);
        $versionId = $this->db->insertID();

        // ---- Domains (Annex A Themes) ---------------------------------------
        $domains = [
            ['code' => 'A.5',  'name' => 'Organizational controls',         'sort_order' => 1],
            ['code' => 'A.6',  'name' => 'People controls',                  'sort_order' => 2],
            ['code' => 'A.7',  'name' => 'Physical controls',                'sort_order' => 3],
            ['code' => 'A.8',  'name' => 'Technological controls',           'sort_order' => 4],
        ];

        foreach ($domains as &$d) {
            $d['standard_version_id'] = $versionId;
            $d['created_at'] = $now;
            $d['updated_at'] = $now;
        }
        unset($d);

        $this->db->table('domains')->insertBatch($domains);

        // Fetch domain IDs
        $domainRows = $this->db->table('domains')
            ->where('standard_version_id', $versionId)
            ->orderBy('sort_order', 'ASC')
            ->get()->getResultArray();

        $domainIds = [];
        foreach ($domainRows as $row) {
            $domainIds[$row['code']] = $row['id'];
        }

        // ---- Controls data --------------------------------------------------
        // Format: [domain_code, clause_code, clause_title, [[ctrl_code, ctrl_title], ...]]
        $data = $this->getControlsData();

        foreach ($data as [$domCode, $clauseCode, $clauseTitle, $controls]) {
            $domainId = $domainIds[$domCode] ?? null;
            if (! $domainId) continue;

            // Insert clause
            $this->db->table('clauses')->insert([
                'domain_id'   => $domainId,
                'code'        => $clauseCode,
                'title'       => $clauseTitle,
                'sort_order'  => 0,
                'created_at'  => $now,
                'updated_at'  => $now,
            ]);
            $clauseId = $this->db->insertID();

            // Insert controls
            $batch = [];
            foreach ($controls as $i => [$ctrlCode, $ctrlTitle]) {
                $batch[] = [
                    'clause_id'   => $clauseId,
                    'code'        => $ctrlCode,
                    'title'       => $ctrlTitle,
                    'sort_order'  => $i + 1,
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ];
            }
            $this->db->table('controls')->insertBatch($batch);
        }
    }

    public function down()
    {
        $version = $this->db->table('standard_versions')->where('version', '2022')
            ->join('standards', 'standards.id = standard_versions.standard_id')
            ->where('standards.code', 'ISO27001')
            ->get()->getRowArray();

        if (! $version) return;

        $domainIds = $this->db->table('domains')
            ->where('standard_version_id', $version['id'])
            ->get()->getResultArray();

        foreach ($domainIds as $d) {
            $clauseIds = $this->db->table('clauses')
                ->where('domain_id', $d['id'])
                ->get()->getResultArray();
            foreach ($clauseIds as $c) {
                $this->db->table('controls')->where('clause_id', $c['id'])->delete();
            }
            $this->db->table('clauses')->where('domain_id', $d['id'])->delete();
        }

        $this->db->table('domains')->where('standard_version_id', $version['id'])->delete();
        $this->db->table('standard_versions')->where('id', $version['id'])->delete();
        $this->db->table('standards')->where('code', 'ISO27001')->delete();
    }

    // -------------------------------------------------------------------------
    private function getControlsData(): array
    {
        return [
            // A.5 Organizational controls (37 controls)
            ['A.5', 'A.5.1', 'Policies for information security', [
                ['A.5.1', 'Policies for information security'],
            ]],
            ['A.5', 'A.5.2', 'Information security roles and responsibilities', [
                ['A.5.2', 'Information security roles and responsibilities'],
            ]],
            ['A.5', 'A.5.3', 'Segregation of duties', [
                ['A.5.3', 'Segregation of duties'],
            ]],
            ['A.5', 'A.5.4', 'Management responsibilities', [
                ['A.5.4', 'Management responsibilities'],
            ]],
            ['A.5', 'A.5.5', 'Contact with authorities', [
                ['A.5.5', 'Contact with authorities'],
            ]],
            ['A.5', 'A.5.6', 'Contact with special interest groups', [
                ['A.5.6', 'Contact with special interest groups'],
            ]],
            ['A.5', 'A.5.7', 'Threat intelligence', [
                ['A.5.7', 'Threat intelligence'],
            ]],
            ['A.5', 'A.5.8', 'Information security in project management', [
                ['A.5.8', 'Information security in project management'],
            ]],
            ['A.5', 'A.5.9', 'Inventory of information and other associated assets', [
                ['A.5.9', 'Inventory of information and other associated assets'],
            ]],
            ['A.5', 'A.5.10', 'Acceptable use of information and other associated assets', [
                ['A.5.10', 'Acceptable use of information and other associated assets'],
            ]],
            ['A.5', 'A.5.11', 'Return of assets', [
                ['A.5.11', 'Return of assets'],
            ]],
            ['A.5', 'A.5.12', 'Classification of information', [
                ['A.5.12', 'Classification of information'],
            ]],
            ['A.5', 'A.5.13', 'Labelling of information', [
                ['A.5.13', 'Labelling of information'],
            ]],
            ['A.5', 'A.5.14', 'Information transfer', [
                ['A.5.14', 'Information transfer'],
            ]],
            ['A.5', 'A.5.15', 'Access control', [
                ['A.5.15', 'Access control'],
            ]],
            ['A.5', 'A.5.16', 'Identity management', [
                ['A.5.16', 'Identity management'],
            ]],
            ['A.5', 'A.5.17', 'Authentication information', [
                ['A.5.17', 'Authentication information'],
            ]],
            ['A.5', 'A.5.18', 'Access rights', [
                ['A.5.18', 'Access rights'],
            ]],
            ['A.5', 'A.5.19', 'Information security in supplier relationships', [
                ['A.5.19', 'Information security in supplier relationships'],
            ]],
            ['A.5', 'A.5.20', 'Addressing information security within supplier agreements', [
                ['A.5.20', 'Addressing information security within supplier agreements'],
            ]],
            ['A.5', 'A.5.21', 'Managing information security in the ICT supply chain', [
                ['A.5.21', 'Managing information security in the ICT supply chain'],
            ]],
            ['A.5', 'A.5.22', 'Monitoring, review and change management of supplier services', [
                ['A.5.22', 'Monitoring, review and change management of supplier services'],
            ]],
            ['A.5', 'A.5.23', 'Information security for use of cloud services', [
                ['A.5.23', 'Information security for use of cloud services'],
            ]],
            ['A.5', 'A.5.24', 'Information security incident management planning and preparation', [
                ['A.5.24', 'Information security incident management planning and preparation'],
            ]],
            ['A.5', 'A.5.25', 'Assessment and decision on information security events', [
                ['A.5.25', 'Assessment and decision on information security events'],
            ]],
            ['A.5', 'A.5.26', 'Response to information security incidents', [
                ['A.5.26', 'Response to information security incidents'],
            ]],
            ['A.5', 'A.5.27', 'Learning from information security incidents', [
                ['A.5.27', 'Learning from information security incidents'],
            ]],
            ['A.5', 'A.5.28', 'Collection of evidence', [
                ['A.5.28', 'Collection of evidence'],
            ]],
            ['A.5', 'A.5.29', 'Information security during disruption', [
                ['A.5.29', 'Information security during disruption'],
            ]],
            ['A.5', 'A.5.30', 'ICT readiness for business continuity', [
                ['A.5.30', 'ICT readiness for business continuity'],
            ]],
            ['A.5', 'A.5.31', 'Legal, statutory, regulatory and contractual requirements', [
                ['A.5.31', 'Legal, statutory, regulatory and contractual requirements'],
            ]],
            ['A.5', 'A.5.32', 'Intellectual property rights', [
                ['A.5.32', 'Intellectual property rights'],
            ]],
            ['A.5', 'A.5.33', 'Protection of records', [
                ['A.5.33', 'Protection of records'],
            ]],
            ['A.5', 'A.5.34', 'Privacy and protection of PII', [
                ['A.5.34', 'Privacy and protection of PII'],
            ]],
            ['A.5', 'A.5.35', 'Independent review of information security', [
                ['A.5.35', 'Independent review of information security'],
            ]],
            ['A.5', 'A.5.36', 'Compliance with policies, rules and standards for information security', [
                ['A.5.36', 'Compliance with policies, rules and standards for information security'],
            ]],
            ['A.5', 'A.5.37', 'Documented operating procedures', [
                ['A.5.37', 'Documented operating procedures'],
            ]],

            // A.6 People controls (8 controls)
            ['A.6', 'A.6.1', 'Screening', [
                ['A.6.1', 'Screening'],
            ]],
            ['A.6', 'A.6.2', 'Terms and conditions of employment', [
                ['A.6.2', 'Terms and conditions of employment'],
            ]],
            ['A.6', 'A.6.3', 'Information security awareness, education and training', [
                ['A.6.3', 'Information security awareness, education and training'],
            ]],
            ['A.6', 'A.6.4', 'Disciplinary process', [
                ['A.6.4', 'Disciplinary process'],
            ]],
            ['A.6', 'A.6.5', 'Responsibilities after termination or change of employment', [
                ['A.6.5', 'Responsibilities after termination or change of employment'],
            ]],
            ['A.6', 'A.6.6', 'Confidentiality or non-disclosure agreements', [
                ['A.6.6', 'Confidentiality or non-disclosure agreements'],
            ]],
            ['A.6', 'A.6.7', 'Remote working', [
                ['A.6.7', 'Remote working'],
            ]],
            ['A.6', 'A.6.8', 'Information security event reporting', [
                ['A.6.8', 'Information security event reporting'],
            ]],

            // A.7 Physical controls (14 controls)
            ['A.7', 'A.7.1', 'Physical security perimeters', [
                ['A.7.1', 'Physical security perimeters'],
            ]],
            ['A.7', 'A.7.2', 'Physical entry', [
                ['A.7.2', 'Physical entry'],
            ]],
            ['A.7', 'A.7.3', 'Securing offices, rooms and facilities', [
                ['A.7.3', 'Securing offices, rooms and facilities'],
            ]],
            ['A.7', 'A.7.4', 'Physical security monitoring', [
                ['A.7.4', 'Physical security monitoring'],
            ]],
            ['A.7', 'A.7.5', 'Protecting against physical and environmental threats', [
                ['A.7.5', 'Protecting against physical and environmental threats'],
            ]],
            ['A.7', 'A.7.6', 'Working in secure areas', [
                ['A.7.6', 'Working in secure areas'],
            ]],
            ['A.7', 'A.7.7', 'Clear desk and clear screen', [
                ['A.7.7', 'Clear desk and clear screen'],
            ]],
            ['A.7', 'A.7.8', 'Equipment siting and protection', [
                ['A.7.8', 'Equipment siting and protection'],
            ]],
            ['A.7', 'A.7.9', 'Security of assets off-premises', [
                ['A.7.9', 'Security of assets off-premises'],
            ]],
            ['A.7', 'A.7.10', 'Storage media', [
                ['A.7.10', 'Storage media'],
            ]],
            ['A.7', 'A.7.11', 'Supporting utilities', [
                ['A.7.11', 'Supporting utilities'],
            ]],
            ['A.7', 'A.7.12', 'Cabling security', [
                ['A.7.12', 'Cabling security'],
            ]],
            ['A.7', 'A.7.13', 'Equipment maintenance', [
                ['A.7.13', 'Equipment maintenance'],
            ]],
            ['A.7', 'A.7.14', 'Secure disposal or re-use of equipment', [
                ['A.7.14', 'Secure disposal or re-use of equipment'],
            ]],

            // A.8 Technological controls (34 controls)
            ['A.8', 'A.8.1', 'User endpoint devices', [
                ['A.8.1', 'User endpoint devices'],
            ]],
            ['A.8', 'A.8.2', 'Privileged access rights', [
                ['A.8.2', 'Privileged access rights'],
            ]],
            ['A.8', 'A.8.3', 'Information access restriction', [
                ['A.8.3', 'Information access restriction'],
            ]],
            ['A.8', 'A.8.4', 'Access to source code', [
                ['A.8.4', 'Access to source code'],
            ]],
            ['A.8', 'A.8.5', 'Secure authentication', [
                ['A.8.5', 'Secure authentication'],
            ]],
            ['A.8', 'A.8.6', 'Capacity management', [
                ['A.8.6', 'Capacity management'],
            ]],
            ['A.8', 'A.8.7', 'Protection against malware', [
                ['A.8.7', 'Protection against malware'],
            ]],
            ['A.8', 'A.8.8', 'Management of technical vulnerabilities', [
                ['A.8.8', 'Management of technical vulnerabilities'],
            ]],
            ['A.8', 'A.8.9', 'Configuration management', [
                ['A.8.9', 'Configuration management'],
            ]],
            ['A.8', 'A.8.10', 'Information deletion', [
                ['A.8.10', 'Information deletion'],
            ]],
            ['A.8', 'A.8.11', 'Data masking', [
                ['A.8.11', 'Data masking'],
            ]],
            ['A.8', 'A.8.12', 'Data leakage prevention', [
                ['A.8.12', 'Data leakage prevention'],
            ]],
            ['A.8', 'A.8.13', 'Information backup', [
                ['A.8.13', 'Information backup'],
            ]],
            ['A.8', 'A.8.14', 'Redundancy of information processing facilities', [
                ['A.8.14', 'Redundancy of information processing facilities'],
            ]],
            ['A.8', 'A.8.15', 'Logging', [
                ['A.8.15', 'Logging'],
            ]],
            ['A.8', 'A.8.16', 'Monitoring activities', [
                ['A.8.16', 'Monitoring activities'],
            ]],
            ['A.8', 'A.8.17', 'Clock synchronization', [
                ['A.8.17', 'Clock synchronization'],
            ]],
            ['A.8', 'A.8.18', 'Use of privileged utility programs', [
                ['A.8.18', 'Use of privileged utility programs'],
            ]],
            ['A.8', 'A.8.19', 'Installation of software on operational systems', [
                ['A.8.19', 'Installation of software on operational systems'],
            ]],
            ['A.8', 'A.8.20', 'Networks security', [
                ['A.8.20', 'Networks security'],
            ]],
            ['A.8', 'A.8.21', 'Security of network services', [
                ['A.8.21', 'Security of network services'],
            ]],
            ['A.8', 'A.8.22', 'Segregation of networks', [
                ['A.8.22', 'Segregation of networks'],
            ]],
            ['A.8', 'A.8.23', 'Web filtering', [
                ['A.8.23', 'Web filtering'],
            ]],
            ['A.8', 'A.8.24', 'Use of cryptography', [
                ['A.8.24', 'Use of cryptography'],
            ]],
            ['A.8', 'A.8.25', 'Secure development life cycle', [
                ['A.8.25', 'Secure development life cycle'],
            ]],
            ['A.8', 'A.8.26', 'Application security requirements', [
                ['A.8.26', 'Application security requirements'],
            ]],
            ['A.8', 'A.8.27', 'Secure system architecture and engineering principles', [
                ['A.8.27', 'Secure system architecture and engineering principles'],
            ]],
            ['A.8', 'A.8.28', 'Secure coding', [
                ['A.8.28', 'Secure coding'],
            ]],
            ['A.8', 'A.8.29', 'Security testing in development and acceptance', [
                ['A.8.29', 'Security testing in development and acceptance'],
            ]],
            ['A.8', 'A.8.30', 'Outsourced development', [
                ['A.8.30', 'Outsourced development'],
            ]],
            ['A.8', 'A.8.31', 'Separation of development, test and production environments', [
                ['A.8.31', 'Separation of development, test and production environments'],
            ]],
            ['A.8', 'A.8.32', 'Change management', [
                ['A.8.32', 'Change management'],
            ]],
            ['A.8', 'A.8.33', 'Test information', [
                ['A.8.33', 'Test information'],
            ]],
            ['A.8', 'A.8.34', 'Protection of information systems during audit testing', [
                ['A.8.34', 'Protection of information systems during audit testing'],
            ]],
        ];
    }
}

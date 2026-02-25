<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Adds name_fr / title_fr columns to domains, clauses and controls,
 * then populates French translations for the ISO 27001:2022 catalogue.
 */
class AddFrenchTranslationsToHierarchy extends Migration
{
    public function up(): void
    {
        // ── 1. Add columns ─────────────────────────────────────────────────

        $this->forge->addColumn('domains', [
            'name_fr' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'after'      => 'name',
            ],
        ]);

        $this->forge->addColumn('clauses', [
            'title_fr' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'after'      => 'title',
            ],
        ]);

        $this->forge->addColumn('controls', [
            'title_fr' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'after'      => 'title',
            ],
        ]);

        // ── 2. Domain French names ──────────────────────────────────────────

        $domainTranslations = [
            'A.5' => 'Mesures organisationnelles',
            'A.6' => 'Mesures liées aux personnes',
            'A.7' => 'Mesures physiques',
            'A.8' => 'Mesures technologiques',
        ];

        foreach ($domainTranslations as $code => $nameFr) {
            $this->db->table('domains')
                ->where('code', $code)
                ->update(['name_fr' => $nameFr]);
        }

        // ── 3. Clause + Control French titles (same code, same translation) ─

        $translations = [
            // A.5 — Mesures organisationnelles (37)
            'A.5.1'  => "Politiques de sécurité de l'information",
            'A.5.2'  => "Rôles et responsabilités en sécurité de l'information",
            'A.5.3'  => "Séparation des tâches",
            'A.5.4'  => "Responsabilités de la direction",
            'A.5.5'  => "Contact avec les autorités",
            'A.5.6'  => "Contact avec des groupes d'intérêt particuliers",
            'A.5.7'  => "Renseignements sur les menaces",
            'A.5.8'  => "Sécurité de l'information dans la gestion de projet",
            'A.5.9'  => "Inventaire des informations et des autres actifs associés",
            'A.5.10' => "Utilisation acceptable des informations et autres actifs associés",
            'A.5.11' => "Restitution des actifs",
            'A.5.12' => "Classification des informations",
            'A.5.13' => "Marquage des informations",
            'A.5.14' => "Transfert d'information",
            'A.5.15' => "Contrôle d'accès",
            'A.5.16' => "Gestion des identités",
            'A.5.17' => "Informations d'authentification",
            'A.5.18' => "Droits d'accès",
            'A.5.19' => "Sécurité de l'information dans les relations avec les fournisseurs",
            'A.5.20' => "Prise en compte de la sécurité de l'information dans les accords avec les fournisseurs",
            'A.5.21' => "Gestion de la sécurité de l'information dans la chaîne d'approvisionnement TIC",
            'A.5.22' => "Surveillance, révision et gestion des changements des services fournisseurs",
            'A.5.23' => "Sécurité de l'information pour l'utilisation des services cloud",
            'A.5.24' => "Planification et préparation de la gestion des incidents de sécurité de l'information",
            'A.5.25' => "Évaluation et décision sur les événements de sécurité de l'information",
            'A.5.26' => "Réponse aux incidents de sécurité de l'information",
            'A.5.27' => "Apprentissage à partir des incidents de sécurité de l'information",
            'A.5.28' => "Collecte de preuves",
            'A.5.29' => "Sécurité de l'information en cas de perturbation",
            'A.5.30' => "Préparation des TIC pour la continuité des activités",
            'A.5.31' => "Exigences légales, statutaires, réglementaires et contractuelles",
            'A.5.32' => "Droits de propriété intellectuelle",
            'A.5.33' => "Protection des enregistrements",
            'A.5.34' => "Vie privée et protection des données à caractère personnel",
            'A.5.35' => "Revue indépendante de la sécurité de l'information",
            'A.5.36' => "Conformité aux politiques, règles et normes de sécurité de l'information",
            'A.5.37' => "Procédures d'exploitation documentées",

            // A.6 — Mesures liées aux personnes (8)
            'A.6.1'  => "Vérification des antécédents",
            'A.6.2'  => "Conditions d'emploi",
            'A.6.3'  => "Sensibilisation, éducation et formation à la sécurité de l'information",
            'A.6.4'  => "Processus disciplinaire",
            'A.6.5'  => "Responsabilités après la fin ou le changement d'emploi",
            'A.6.6'  => "Accords de confidentialité ou de non-divulgation",
            'A.6.7'  => "Télétravail",
            'A.6.8'  => "Signalement des événements liés à la sécurité de l'information",

            // A.7 — Mesures physiques (14)
            'A.7.1'  => "Périmètres de sécurité physique",
            'A.7.2'  => "Contrôle des accès physiques",
            'A.7.3'  => "Sécurisation des bureaux, salles et locaux",
            'A.7.4'  => "Surveillance de la sécurité physique",
            'A.7.5'  => "Protection contre les menaces physiques et environnementales",
            'A.7.6'  => "Travail en zones sécurisées",
            'A.7.7'  => "Bureau propre et écran propre",
            'A.7.8'  => "Emplacement et protection du matériel",
            'A.7.9'  => "Sécurité des actifs hors site",
            'A.7.10' => "Supports de stockage",
            'A.7.11' => "Services supports",
            'A.7.12' => "Sécurité du câblage",
            'A.7.13' => "Maintenance du matériel",
            'A.7.14' => "Élimination ou réutilisation sécurisée du matériel",

            // A.8 — Mesures technologiques (34)
            'A.8.1'  => "Terminaux utilisateurs",
            'A.8.2'  => "Droits d'accès privilégiés",
            'A.8.3'  => "Restriction d'accès à l'information",
            'A.8.4'  => "Accès au code source",
            'A.8.5'  => "Authentification sécurisée",
            'A.8.6'  => "Gestion de la capacité",
            'A.8.7'  => "Protection contre les logiciels malveillants",
            'A.8.8'  => "Gestion des vulnérabilités techniques",
            'A.8.9'  => "Gestion de la configuration",
            'A.8.10' => "Suppression d'information",
            'A.8.11' => "Masquage des données",
            'A.8.12' => "Prévention des fuites de données",
            'A.8.13' => "Sauvegarde des informations",
            'A.8.14' => "Redondance des moyens de traitement de l'information",
            'A.8.15' => "Journalisation",
            'A.8.16' => "Activités de surveillance",
            'A.8.17' => "Synchronisation des horloges",
            'A.8.18' => "Utilisation de programmes utilitaires à privilèges",
            'A.8.19' => "Installation de logiciels sur les systèmes opérationnels",
            'A.8.20' => "Sécurité des réseaux",
            'A.8.21' => "Sécurité des services réseau",
            'A.8.22' => "Cloisonnement des réseaux",
            'A.8.23' => "Filtrage web",
            'A.8.24' => "Utilisation de la cryptographie",
            'A.8.25' => "Cycle de développement sécurisé",
            'A.8.26' => "Exigences de sécurité des applications",
            'A.8.27' => "Architecture système sécurisée et principes d'ingénierie",
            'A.8.28' => "Codage sécurisé",
            'A.8.29' => "Tests de sécurité dans le développement et l'acceptation",
            'A.8.30' => "Développement externalisé",
            'A.8.31' => "Séparation des environnements de développement, de test et de production",
            'A.8.32' => "Gestion des changements",
            'A.8.33' => "Informations de test",
            'A.8.34' => "Protection des systèmes d'information lors des tests d'audit",
        ];

        foreach ($translations as $code => $titleFr) {
            $this->db->table('clauses')
                ->where('code', $code)
                ->update(['title_fr' => $titleFr]);

            $this->db->table('controls')
                ->where('code', $code)
                ->update(['title_fr' => $titleFr]);
        }
    }

    public function down(): void
    {
        $this->forge->dropColumn('controls', 'title_fr');
        $this->forge->dropColumn('clauses', 'title_fr');
        $this->forge->dropColumn('domains', 'name_fr');
    }
}

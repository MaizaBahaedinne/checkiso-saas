<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Seeds quiz questions & choices for every ISO 27001:2022 control.
 *
 * Choice scoring:
 *   score_pct = 100 → Conforme
 *   score_pct = 60  → Partiel (good intent, incomplete)
 *   score_pct = 30  → Partiel faible (trap — sounds good, actually weak)
 *   score_pct = 0   → Non-conforme
 *
 * is_trap = 1 → choice wording deliberately misleads (sounds compliant, isn't)
 * requires_justification = 1 → user must explain their answer
 * is_manual_review = 1 → answer cannot be auto-scored (e.g. "Autre")
 */
class SeedControlQuestions extends Migration
{
    private string $now;

    public function up()
    {
        $this->now = date('Y-m-d H:i:s');

        // Fetch all controls joined to their code
        $controls = $this->db->table('controls c')
            ->select('c.id, c.code, c.title')
            ->join('clauses cl', 'cl.id = c.clause_id')
            ->join('domains d',  'd.id = cl.domain_id')
            ->join('standard_versions sv', 'sv.id = d.standard_version_id')
            ->join('standards s', 's.id = sv.standard_id')
            ->where('s.code', 'ISO27001')
            ->where('sv.version', '2022')
            ->orderBy('c.code', 'ASC')
            ->get()->getResultArray();

        foreach ($controls as $ctrl) {
            $this->seedControl($ctrl['id'], $ctrl['code'], $ctrl['title']);
        }
    }

    // ── Dispatcher ────────────────────────────────────────────────────────────
    private function seedControl(int $id, string $code, string $title): void
    {
        $data = $this->getQuestions();
        if (isset($data[$code])) {
            [$question, $hint, $choices] = $data[$code];
        } else {
            // Generic fallback question for any unspecified control
            [$question, $hint, $choices] = $this->genericQuestion($title);
        }

        $this->db->table('control_questions')->insert([
            'control_id' => $id,
            'question'   => $question,
            'hint'       => $hint,
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);
        $qid = $this->db->insertID();

        foreach ($choices as $i => $c) {
            $this->db->table('control_choices')->insert([
                'question_id'            => $qid,
                'choice_key'             => $c[0],
                'label'                  => $c[1],
                'score_pct'              => $c[2],
                'status'                 => $c[2] >= 90 ? 'conforme' : ($c[2] >= 50 ? 'partiel' : 'non_conforme'),
                'is_trap'                => $c[3] ?? 0,
                'requires_justification' => $c[4] ?? 0,
                'is_manual_review'       => $c[5] ?? 0,
                'sort_order'             => $i + 1,
                'created_at'             => $this->now,
                'updated_at'             => $this->now,
            ]);
        }
    }

    // ── Generic fallback ─────────────────────────────────────────────────────
    private function genericQuestion(string $title): array
    {
        return [
            "Concernant « {$title} » : quel est le niveau de mise en œuvre dans votre organisation ?",
            "Évaluez honnêtement la réalité opérationnelle, pas les intentions.",
            [
                ['a', "Mis en œuvre, documenté, testé et revu régulièrement.", 100, 0, 0, 0],
                ['b', "Mis en œuvre et documenté, mais jamais testé formellement.", 60, 0, 1, 0],
                ['c', "Nous en parlons en réunion mais rien n'est formalisé — tout le monde sait ce qu'il faut faire.", 30, 1, 0, 0],
                ['d', "Non mis en œuvre.", 0, 0, 0, 0],
                ['e', "Autre situation — précisez.", 0, 0, 0, 1],
            ],
        ];
    }

    // ── Questions catalogue ───────────────────────────────────────────────────
    private function getQuestions(): array
    {
        return [

            // ─── A.5 Organizational controls ─────────────────────────────────

            'A.5.1' => [
                "Votre organisation dispose-t-elle d'une politique de sécurité de l'information formellement approuvée par la direction ?",
                "La politique doit être approuvée, publiée, communiquée et revue périodiquement (au moins annuellement).",
                [
                    ['a', "Oui — politique approuvée par la direction, diffusée à tous les employés et revue tous les ans.", 100, 0, 0, 0],
                    ['b', "Oui — politique rédigée et validée, mais la dernière révision date de plus de 2 ans.", 60, 0, 1, 0],
                    ['c', "Nous avons un document interne de bonnes pratiques connu de tous — c'est suffisant.", 30, 1, 0, 0],
                    ['d', "Non, aucune politique formelle.", 0, 0, 0, 0],
                    ['e', "Autre — décrivez la situation.", 0, 0, 0, 1],
                ],
            ],

            'A.5.2' => [
                "Les rôles et responsabilités en matière de sécurité de l'information sont-ils définis et attribués ?",
                "Chaque rôle clé (RSSI ou équivalent, propriétaires d'actifs, utilisateurs) doit avoir des responsabilités documentées.",
                [
                    ['a', "Oui — RSSI nommé, responsabilités documentées pour chaque rôle et communiquées.", 100, 0, 0, 0],
                    ['b', "Le responsable IT s'occupe de la sécurité en plus de ses autres missions, sans fiche de poste dédiée.", 30, 1, 0, 0],
                    ['c', "Des rôles sont identifiés mais les responsabilités ne sont pas formalisées par écrit.", 60, 0, 1, 0],
                    ['d', "Aucun rôle dédié à la sécurité de l'information.", 0, 0, 0, 0],
                    ['e', "Autre situation.", 0, 0, 0, 1],
                ],
            ],

            'A.5.3' => [
                "La séparation des tâches est-elle appliquée pour prévenir les conflits d'intérêts et les fraudes ?",
                "Aucune personne ne doit pouvoir initier et autoriser seule une opération sensible.",
                [
                    ['a', "Oui — matrice RACI en place, contrôles techniques empêchant les cumuls de droits critiques.", 100, 0, 0, 0],
                    ['b', "La direction fait confiance à ses équipes, la séparation formelle n'est pas jugée nécessaire.", 0, 1, 0, 0],
                    ['c', "Séparation appliquée pour certains processus financiers, mais pas pour les accès IT.", 60, 0, 1, 0],
                    ['d', "Pas de séparation des tâches mise en place.", 0, 0, 0, 0],
                    ['e', "Autre approche.", 0, 0, 0, 1],
                ],
            ],

            'A.5.4' => [
                "La direction démontre-t-elle activement son engagement envers la sécurité de l'information ?",
                "L'engagement de la direction se manifeste par des actes (budget, revues, communication) pas seulement des déclarations.",
                [
                    ['a', "Oui — la direction participe aux revues SMSI, alloue un budget sécurité et communique ses attentes.", 100, 0, 0, 0],
                    ['b', "La direction a signé la politique — son rôle est terminé, c'est maintenant à l'IT de gérer.", 0, 1, 0, 0],
                    ['c', "La direction est informée des incidents mais n'est pas impliquée dans les décisions sécurité.", 30, 0, 1, 0],
                    ['d', "La sécurité est gérée uniquement par l'IT sans implication managériale.", 0, 0, 0, 0],
                    ['e', "Autre.", 0, 0, 0, 1],
                ],
            ],

            'A.5.5' => [
                "Votre organisation maintient-elle des contacts réguliers avec les autorités compétentes (CERT, CNIL, régulateurs) ?",
                "Des contacts doivent être établis avant tout incident, pas uniquement en réaction.",
                [
                    ['a', "Oui — contacts identifiés, procédures de notification documentées, exercices réguliers.", 100, 0, 0, 0],
                    ['b', "On contacte les autorités uniquement en cas d'incident grave.", 30, 1, 0, 0],
                    ['c', "Les contacts sont identifiés mais aucune procédure formelle n'existe.", 60, 0, 1, 0],
                    ['d', "Aucun contact établi avec des autorités.", 0, 0, 0, 0],
                    ['e', "Autre.", 0, 0, 0, 1],
                ],
            ],

            'A.5.6' => [
                "L'organisation participe-t-elle à des groupes d'intérêt spécialisés en sécurité (ISAC, associations, forums) ?",
                "La participation doit apporter un partage de renseignements sur les menaces et les bonnes pratiques.",
                [
                    ['a', "Oui — membre actif d'un ou plusieurs groupes, veille threat-intelligence documentée.", 100, 0, 0, 0],
                    ['b', "Nous lisons des newsletters sécurité — c'est notre veille.", 30, 1, 0, 0],
                    ['c', "Participation ponctuelle à des conférences sans structure formelle.", 60, 0, 1, 0],
                    ['d', "Aucune participation à des groupes de la communauté.", 0, 0, 0, 0],
                    ['e', "Autre.", 0, 0, 0, 1],
                ],
            ],

            'A.5.7' => [
                "Votre organisation collecte et analyse-t-elle des renseignements sur les menaces (threat intelligence) ?",
                "Les renseignements doivent être utilisés pour adapter les contrôles, pas seulement archivés.",
                [
                    ['a', "Oui — flux CTI intégrés, analyses régulières, actions correctives tracées.", 100, 0, 0, 0],
                    ['b', "Nous avons un abonnement à un flux de menaces mais personne n'analyse les alertes.", 30, 1, 0, 0],
                    ['c', "Veille manuelle réalisée par l'équipe IT, sans processus formalisé.", 60, 0, 1, 0],
                    ['d', "Aucune activité de threat intelligence.", 0, 0, 0, 0],
                    ['e', "Autre approche.", 0, 0, 0, 1],
                ],
            ],

            'A.5.8' => [
                "La sécurité de l'information est-elle intégrée dans la gestion de vos projets (méthodologie projet) ?",
                "La sécurité doit être traitée dès la phase de conception (security by design), pas ajoutée en fin de projet.",
                [
                    ['a', "Oui — point sécurité obligatoire dans chaque phase projet, validation RSSI requise.", 100, 0, 0, 0],
                    ['b', "L'équipe sécurité est consultée en fin de projet pour validation avant mise en prod.", 30, 1, 0, 0],
                    ['c', "Une checklist sécurité existe mais son application n'est pas vérifiée.", 60, 0, 1, 0],
                    ['d', "La sécurité n'est pas intégrée au processus projet.", 0, 0, 0, 0],
                    ['e', "Autre.", 0, 0, 0, 1],
                ],
            ],

            'A.5.9' => [
                "Votre organisation tient-elle un inventaire à jour des actifs informationnels et des équipements associés ?",
                "L'inventaire doit être complet, actualisé et associer chaque actif à un propriétaire identifié.",
                [
                    ['a', "Oui — inventaire automatisé, à jour, avec propriétaire, classification et localisation de chaque actif.", 100, 0, 0, 0],
                    ['b', "Nous connaissons nos actifs principaux — un inventaire exhaustif n'est pas nécessaire pour notre taille.", 0, 1, 0, 0],
                    ['c', "Inventaire partiel (serveurs et postes) sans données, applications ni actifs cloud.", 60, 0, 1, 0],
                    ['d', "Pas d'inventaire formalisé.", 0, 0, 0, 0],
                    ['e', "Autre situation.", 0, 0, 0, 1],
                ],
            ],

            'A.5.10' => [
                "Une politique d'utilisation acceptable des actifs informationnels est-elle en place et respectée ?",
                "La politique doit couvrir l'utilisation personnelle, le BYOD, le cloud, et être signée par les utilisateurs.",
                [
                    ['a', "Oui — politique diffusée, signée à l'embauche, rappelée annuellement, violations tracées.", 100, 0, 0, 0],
                    ['b', "La charte informatique est dans le règlement intérieur que personne ne lit vraiment.", 30, 1, 0, 0],
                    ['c', "Politique existante mais non signée par tous les utilisateurs.", 60, 0, 1, 0],
                    ['d', "Aucune politique d'utilisation acceptable.", 0, 0, 0, 0],
                    ['e', "Autre.", 0, 0, 0, 1],
                ],
            ],

            'A.5.11' => [
                "Les actifs sont-ils restitués ou effacés de manière sécurisée lors des départs d'employés ou de prestataires ?",
                "Inclut : PC, téléphones, badges, accès cloud, accès VPN.",
                [
                    ['a', "Oui — checklist de départ formelle, effacement certifié, révocation accès le jour J.", 100, 0, 0, 0],
                    ['b', "L'employé rend le matériel au RH, l'IT est informé après coup.", 30, 1, 0, 0],
                    ['c', "Procédure définie pour le matériel mais les accès logiques ne sont pas tous révoqués le même jour.", 60, 0, 1, 0],
                    ['d', "Pas de procédure de départ formelle.", 0, 0, 0, 0],
                    ['e', "Autre.", 0, 0, 0, 1],
                ],
            ],

            'A.5.12' => [
                "L'information est-elle classifiée selon son niveau de sensibilité dans votre organisation ?",
                "La classification doit être opérationnelle : les utilisateurs savent comment classer leurs documents.",
                [
                    ['a', "Oui — schéma de classification défini (Public / Interne / Confidentiel / Secret), appliqué et contrôlé.", 100, 0, 0, 0],
                    ['b', "Tout est confidentiel chez nous, donc nous n'avons pas besoin de classification.", 0, 1, 0, 0],
                    ['c', "Schéma défini mais peu appliqué faute de formation des utilisateurs.", 30, 0, 1, 0],
                    ['d', "Aucune classification de l'information.", 0, 0, 0, 0],
                    ['e', "Autre.", 0, 0, 0, 1],
                ],
            ],

            'A.5.13' => [
                "Les informations classifiées sont-elles étiquetées conformément au schéma de classification ?",
                "L'étiquetage doit être visible sur les documents et dans les systèmes (métadonnées, en-têtes).",
                [
                    ['a', "Oui — étiquetage appliqué sur tous les supports (documents, emails, dossiers partagés).", 100, 0, 0, 0],
                    ['b', "Nos employés savent ce qui est confidentiel sans avoir besoin d'étiquette.", 0, 1, 0, 0],
                    ['c', "Étiquetage appliqué sur certains supports (papier) mais pas sur les fichiers numériques.", 60, 0, 1, 0],
                    ['d', "Aucun étiquetage en place.", 0, 0, 0, 0],
                    ['e', "Autre.", 0, 0, 0, 1],
                ],
            ],

            'A.5.14' => [
                "Des accords de transfert d'information sont-ils en place pour protéger les données échangées avec des tiers ?",
                "Inclut les transferts par email, messagerie, API, support physique.",
                [
                    ['a', "Oui — NDA / accords de confidentialité systématiques, chiffrement des transferts sensibles.", 100, 0, 0, 0],
                    ['b', "On utilise le chiffrement TLS pour les emails — c'est suffisant.", 30, 1, 0, 0],
                    ['c', "NDA signés avec les partenaires principaux, mais pas de procédure pour les transferts ad hoc.", 60, 0, 1, 0],
                    ['d', "Aucun accord de transfert formalisé.", 0, 0, 0, 0],
                    ['e', "Autre.", 0, 0, 0, 1],
                ],
            ],

            'A.5.15' => [
                "Une politique de contrôle d'accès est-elle définie et appliquée selon le principe du moindre privilège ?",
                "Least privilege : chaque utilisateur n'accède qu'à ce dont il a besoin pour son rôle.",
                [
                    ['a', "Oui — politique documentée, accès accordés sur demande validée, revues régulières.", 100, 0, 0, 0],
                    ['b', "Nous donnons un accès large au départ et restreignons si nécessaire — c'est plus pratique.", 0, 1, 0, 0],
                    ['c', "Politique définie mais les revues d'accès ne sont pas réalisées régulièrement.", 60, 0, 1, 0],
                    ['d', "Aucune politique de contrôle d'accès formelle.", 0, 0, 0, 0],
                    ['e', "Autre.", 0, 0, 0, 1],
                ],
            ],

            'A.5.16' => [
                "La gestion des identités (cycle de vie des comptes) est-elle formalisée et automatisée ?",
                "Création, modification et suppression des comptes doivent suivre un processus traçable.",
                [
                    ['a', "Oui — IAM ou processus formalisé : création sur ticket, désactivation automatique au départ.", 100, 0, 0, 0],
                    ['b', "L'IT crée les comptes à la demande des managers par email.", 30, 1, 0, 0],
                    ['c', "Procédure définie pour la création, mais la suppression est souvent oubliée.", 60, 0, 1, 0],
                    ['d', "Pas de processus formalisé pour la gestion des identités.", 0, 0, 0, 0],
                    ['e', "Autre approche.", 0, 0, 0, 1],
                ],
            ],

            'A.5.17' => [
                "Les informations d'authentification (mots de passe, clés) sont-elles gérées de manière sécurisée ?",
                "Inclut : politique de complexité, pas de mots de passe partagés, gestionnaire de mots de passe, MFA.",
                [
                    ['a', "Oui — politique MDP robuste, MFA sur les accès sensibles, gestionnaire de mots de passe déployé.", 100, 0, 0, 0],
                    ['b', "Nos mots de passe complexes sont notés dans un fichier Excel protégé partagé en interne.", 0, 1, 0, 0],
                    ['c', "Politique de complexité définie, mais pas de MFA ni de gestionnaire de mots de passe.", 60, 0, 1, 0],
                    ['d', "Aucune politique sur les mots de passe.", 0, 0, 0, 0],
                    ['e', "Autre.", 0, 0, 0, 1],
                ],
            ],

            'A.5.18' => [
                "Les droits d'accès sont-ils revus régulièrement et révoqués rapidement en cas de changement de poste ou de départ ?",
                "Des revues d'accès formelles (au moins annuelles) sont requises.",
                [
                    ['a', "Oui — revues d'accès trimestrielles, révocation automatique à J0 du départ.", 100, 0, 0, 0],
                    ['b', "On fait confiance aux managers pour signaler les changements — ça fonctionne bien.", 30, 1, 0, 0],
                    ['c', "Revues réalisées mais de manière informelle et irrégulière.", 60, 0, 1, 0],
                    ['d', "Pas de revue des droits d'accès.", 0, 0, 0, 0],
                    ['e', "Autre.", 0, 0, 0, 1],
                ],
            ],

            'A.5.19' => [
                "La sécurité de l'information est-elle intégrée dans les contrats et la gestion de vos fournisseurs ?",
                "Les exigences sécurité doivent figurer dans les contrats, avec des mécanismes de vérification.",
                [
                    ['a', "Oui — clauses sécurité systématiques, audits fournisseurs annuels, registre des fournisseurs à risque.", 100, 0, 0, 0],
                    ['b', "Nos fournisseurs sont des grandes entreprises reconnues — leur sécurité n'est pas à questionner.", 0, 1, 0, 0],
                    ['c', "Clauses sécurité dans certains contrats mais pas de processus systématique.", 60, 0, 1, 0],
                    ['d', "Aucune exigence sécurité dans les contrats fournisseurs.", 0, 0, 0, 0],
                    ['e', "Autre.", 0, 0, 0, 1],
                ],
            ],

            'A.5.20' => [
                "Les accords avec vos fournisseurs précisent-ils les exigences de sécurité applicables à vos informations ?",
                "Les SLA sécurité, niveaux de confidentialité et droits d'audit doivent être définis.",
                [
                    ['a', "Oui — SLA sécurité, niveaux de confidentialité et droits d'audit stipulés dans tous les contrats.", 100, 0, 0, 0],
                    ['b', "Le contrat mentionne la confidentialité — c'est suffisant.", 30, 1, 0, 0],
                    ['c', "Exigences définies pour les fournisseurs critiques uniquement.", 60, 0, 1, 0],
                    ['d', "Aucune exigence sécurité dans les accords.", 0, 0, 0, 0],
                    ['e', "Autre.", 0, 0, 0, 1],
                ],
            ],

            'A.5.21' => [
                "La sécurité dans la chaîne d'approvisionnement ICT (logiciels, matériels, services cloud) est-elle gérée ?",
                "Risques liés aux composants tiers, bibliothèques open source, dépendances logicielles.",
                [
                    ['a', "Oui — inventaire des composants tiers, analyse SBOM, vérification de l'intégrité des livraisons.", 100, 0, 0, 0],
                    ['b', "Nous utilisons uniquement des logiciels commerciaux reconnus — le risque chaîne d'appro ne nous concerne pas.", 0, 1, 0, 0],
                    ['c', "Sélection rigoureuse des fournisseurs ICT, mais pas d'analyse des composants logiciels.", 60, 0, 1, 0],
                    ['d', "Aucune gestion de la chaîne d'approvisionnement ICT.", 0, 0, 0, 0],
                    ['e', "Autre.", 0, 0, 0, 1],
                ],
            ],

            'A.5.22' => [
                "Les services de vos fournisseurs font-ils l'objet d'une surveillance et d'audits réguliers ?",
                "Inclut revues de performance sécurité, rapports d'audit tiers (SOC2, ISO 27001 du fournisseur).",
                [
                    ['a', "Oui — revues annuelles, rapports SOC2/ISO demandés, incidents fournisseurs tracés.", 100, 0, 0, 0],
                    ['b', "Si le service fonctionne, c'est que tout va bien.", 0, 1, 0, 0],
                    ['c', "Surveillance réactive : on intervient seulement en cas d'incident.", 30, 0, 1, 0],
                    ['d', "Aucune surveillance des fournisseurs.", 0, 0, 0, 0],
                    ['e', "Autre.", 0, 0, 0, 1],
                ],
            ],

            'A.5.23' => [
                "La sécurité pour l'utilisation des services cloud est-elle gérée (responsabilités partagées, configuration) ?",
                "Le modèle de responsabilité partagée doit être compris et documenté pour chaque service cloud.",
                [
                    ['a', "Oui — responsabilités partagées documentées, benchmark CIS Cloud ou équivalent appliqué.", 100, 0, 0, 0],
                    ['b', "Notre hébergeur cloud est certifié ISO 27001 — cela couvre notre sécurité.", 0, 1, 0, 0],
                    ['c', "Configuration sécurisée appliquée mais pas de documentation formelle des responsabilités.", 60, 0, 1, 0],
                    ['d', "Pas de gestion spécifique de la sécurité cloud.", 0, 0, 0, 0],
                    ['e', "Autre.", 0, 0, 0, 1],
                ],
            ],

            'A.5.24' => [
                "La gestion des incidents de sécurité de l'information est-elle planifiée et structurée ?",
                "Un plan de réponse aux incidents doit exister, être testé et connu des parties prenantes.",
                [
                    ['a', "Oui — IRP documenté, équipe dédiée, exercices annuels, post-mortem systématiques.", 100, 0, 0, 0],
                    ['b', "En cas d'incident, toute l'équipe se mobilise spontanément — c'est notre force.", 30, 1, 0, 0],
                    ['c', "Plan de réponse rédigé mais jamais testé ni exercé.", 60, 0, 1, 0],
                    ['d', "Aucun plan de gestion des incidents.", 0, 0, 0, 0],
                    ['e', "Autre.", 0, 0, 0, 1],
                ],
            ],

            'A.5.25' => [
                "Les incidents de sécurité sont-ils évalués, classifiés et traités selon leur criticité ?",
                "La classification doit guider la réponse : escalade, notification, remédiation.",
                [
                    ['a', "Oui — grille de criticité définie, procédure d'escalade documentée, SIEM ou outil de ticketing.", 100, 0, 0, 0],
                    ['b', "Tous les incidents sont traités avec la même urgence — nous ne faisons pas de tri.", 0, 1, 0, 0],
                    ['c', "Classification informelle selon l'expérience de l'équipe IT.", 30, 0, 1, 0],
                    ['d', "Pas de processus de classification des incidents.", 0, 0, 0, 0],
                    ['e', "Autre.", 0, 0, 0, 1],
                ],
            ],

            'A.5.26' => [
                "En cas d'incident, répondez-vous aux cybermenaces de manière structurée et documentée ?",
                "La réponse doit être traçable : qui a fait quoi, quand, avec quelles décisions.",
                [
                    ['a', "Oui — playbooks de réponse par type d'incident, journalisation des actions, chain of custody.", 100, 0, 0, 0],
                    ['b', "On résout le problème le plus vite possible, on ne documente pas pendant la crise.", 30, 1, 0, 0],
                    ['c', "Réponse documentée après coup mais pas de procédure en temps réel.", 60, 0, 1, 0],
                    ['d', "Aucune procédure de réponse aux incidents.", 0, 0, 0, 0],
                    ['e', "Autre.", 0, 0, 0, 1],
                ],
            ],

            'A.5.27' => [
                "Les incidents de sécurité passés sont-ils analysés pour améliorer les contrôles existants ?",
                "Le retour d'expérience (RETEX) est obligatoire pour les incidents significatifs.",
                [
                    ['a', "Oui — post-mortem blameless systématique, lessons learned documentées, plan d'amélioration suivi.", 100, 0, 0, 0],
                    ['b', "Une fois l'incident résolu, on passe à autre chose — regarder en arrière ne sert à rien.", 0, 1, 0, 0],
                    ['c', "Analyse réalisée pour les incidents majeurs uniquement.", 60, 0, 1, 0],
                    ['d', "Pas d'analyse post-incident.", 0, 0, 0, 0],
                    ['e', "Autre.", 0, 0, 0, 1],
                ],
            ],

            'A.5.28' => [
                "Des preuves numériques (forensics) sont-elles collectées et préservées correctement lors d'incidents ?",
                "La collecte doit respecter la chaîne de custody pour que les preuves soient recevables.",
                [
                    ['a', "Oui — procédure forensics documentée, images disque réalisées, chaîne de custody maintenue.", 100, 0, 0, 0],
                    ['b', "On fait des captures d'écran et on envoie les logs par email — c'est notre forensics.", 30, 1, 0, 0],
                    ['c', "Les logs sont conservés mais sans procédure de chaîne de custody.", 60, 0, 1, 0],
                    ['d', "Aucune procédure de collecte de preuves.", 0, 0, 0, 0],
                    ['e', "Autre.", 0, 0, 0, 1],
                ],
            ],

            'A.5.29' => [
                "La continuité de la sécurité de l'information est-elle assurée en cas de crise ou de perturbation majeure ?",
                "Des mesures de sécurité doivent rester opérationnelles même en mode dégradé.",
                [
                    ['a', "Oui — plan de continuité de la sécurité testé, intégré au PCA global.", 100, 0, 0, 0],
                    ['b', "Notre PCA couvre la continuité métier — la sécurité se rétablit une fois la crise passée.", 0, 1, 0, 0],
                    ['c', "PCA existant mais la sécurité en mode dégradé n'est pas spécifiquement traitée.", 60, 0, 1, 0],
                    ['d', "Pas de plan de continuité.", 0, 0, 0, 0],
                    ['e', "Autre.", 0, 0, 0, 1],
                ],
            ],

            'A.5.30' => [
                "La préparation aux perturbations ICT (pannes, cyberattaques, catastrophes) est-elle évaluée et testée ?",
                "Tests de reprise (DRP), RTO/RPO définis et vérifiés.",
                [
                    ['a', "Oui — RTO/RPO définis, DRP testé annuellement, résultats documentés.", 100, 0, 0, 0],
                    ['b', "Notre sauvegarde cloud est automatique — la reprise sera forcément rapide.", 30, 1, 0, 0],
                    ['c', "DRP rédigé mais jamais testé.", 60, 0, 1, 0],
                    ['d', "Aucune préparation aux perturbations ICT.", 0, 0, 0, 0],
                    ['e', "Autre.", 0, 0, 0, 1],
                ],
            ],

            'A.5.31' => [
                "Les exigences légales, réglementaires et contractuelles relatives à la sécurité sont-elles identifiées ?",
                "RGPD, NIS2, réglementations sectorielles, obligations contractuelles clients.",
                [
                    ['a', "Oui — registre des exigences légales maintenu, responsable juridique impliqué, veille réglementaire.", 100, 0, 0, 0],
                    ['b', "Le RGPD ne s'applique pas vraiment à notre activité — nous ne traitons pas de données personnelles sensibles.", 0, 1, 0, 0],
                    ['c', "RGPD identifié, mais autres réglementations sectorielles pas analysées.", 60, 0, 1, 0],
                    ['d', "Aucune identification formelle des exigences légales.", 0, 0, 0, 0],
                    ['e', "Autre.", 0, 0, 0, 1],
                ],
            ],

            'A.5.32' => [
                "Les droits de propriété intellectuelle (licences, brevets, droits d'auteur) sont-ils respectés ?",
                "Inclut logiciels, contenus, bases de données.",
                [
                    ['a', "Oui — registre des licences, outil de gestion SAM, audits de conformité licences.", 100, 0, 0, 0],
                    ['b', "Nos équipes savent qu'elles ne doivent pas pirater des logiciels.", 30, 1, 0, 0],
                    ['c', "Licences gérées pour les logiciels principaux mais pas pour les outils annexes.", 60, 0, 1, 0],
                    ['d', "Aucune gestion formelle des licences.", 0, 0, 0, 0],
                    ['e', "Autre.", 0, 0, 0, 1],
                ],
            ],

            'A.5.33' => [
                "Les enregistrements importants (logs, contrats, données financières) sont-ils protégés contre modification et perte ?",
                "Intégrité, disponibilité et durée de conservation légale.",
                [
                    ['a', "Oui — stockage immuable (WORM), durées de conservation définies, accès journalisés.", 100, 0, 0, 0],
                    ['b', "Les enregistrements sont sur un serveur de fichiers partagé — seuls les admins peuvent les modifier.", 30, 1, 0, 0],
                    ['c', "Conservation assurée mais intégrité non vérifiable cryptographiquement.", 60, 0, 1, 0],
                    ['d', "Pas de mesures spécifiques de protection des enregistrements.", 0, 0, 0, 0],
                    ['e', "Autre.", 0, 0, 0, 1],
                ],
            ],

            'A.5.34' => [
                "La vie privée et la protection des données personnelles sont-elles assurées conformément au RGPD ?",
                "DPO nommé si requis, registre des traitements, DPIA pour traitements à risque.",
                [
                    ['a', "Oui — DPO nommé/désigné, registre des traitements complet, DPIA réalisées.", 100, 0, 0, 0],
                    ['b', "Nous avons mis à jour notre politique de confidentialité sur le site — c'est notre conformité RGPD.", 0, 1, 0, 0],
                    ['c', "Registre des traitements existant mais DPIA non réalisées.", 60, 0, 1, 0],
                    ['d', "Aucune démarche RGPD formalisée.", 0, 0, 0, 0],
                    ['e', "Autre.", 0, 0, 0, 1],
                ],
            ],

            'A.5.35' => [
                "La sécurité de l'information fait-elle l'objet de revues indépendantes régulières (audits internes/externes) ?",
                "Les revues doivent être réalisées par des personnes indépendantes de l'équipe auditée.",
                [
                    ['a', "Oui — audits internes annuels et audit externe tous les 3 ans minimum, rapports tracés.", 100, 0, 0, 0],
                    ['b', "Notre équipe IT s'auto-évalue régulièrement — c'est plus efficace et moins coûteux.", 0, 1, 0, 0],
                    ['c', "Audit interne réalisé par l'IT mais sans prestataire externe.", 60, 0, 1, 0],
                    ['d', "Aucune revue indépendante.", 0, 0, 0, 0],
                    ['e', "Autre.", 0, 0, 0, 1],
                ],
            ],

            'A.5.36' => [
                "La conformité aux politiques et procédures de sécurité est-elle vérifiée régulièrement ?",
                "Revues de conformité distinctes des audits : contrôles périodiques internes.",
                [
                    ['a', "Oui — contrôles de conformité planifiés, résultats documentés, non-conformités tracées.", 100, 0, 0, 0],
                    ['b', "Si personne ne se plaint, c'est que les règles sont respectées.", 0, 1, 0, 0],
                    ['c', "Vérifications réalisées ponctuellement sans plan formel.", 60, 0, 1, 0],
                    ['d', "Pas de vérification de la conformité aux politiques.", 0, 0, 0, 0],
                    ['e', "Autre.", 0, 0, 0, 1],
                ],
            ],

            'A.5.37' => [
                "Les procédures d'exploitation des systèmes sont-elles documentées et accessibles aux personnes concernées ?",
                "Runbooks, procédures d'administration, modes opératoires — à jour et versionnés.",
                [
                    ['a', "Oui — documentation à jour dans un wiki/ITSM, versionnée et revue régulièrement.", 100, 0, 0, 0],
                    ['b', "Nos techniciens expérimentés n'ont pas besoin de documentation — ils connaissent les systèmes.", 0, 1, 0, 0],
                    ['c', "Documentation partielle, souvent obsolète.", 30, 0, 1, 0],
                    ['d', "Aucune documentation des procédures d'exploitation.", 0, 0, 0, 0],
                    ['e', "Autre.", 0, 0, 0, 1],
                ],
            ],

            // ─── A.6 People controls ──────────────────────────────────────────

            'A.6.1' => [
                "Des vérifications des antécédents sont-elles effectuées avant l'embauche (background checks) ?",
                "Niveau de vérification proportionnel au niveau d'accès du poste.",
                [
                    ['a', "Oui — vérifications proportionnelles au poste : CV, diplômes, casier judiciaire si requis.", 100, 0, 0, 0],
                    ['b', "Nous faisons confiance aux candidats — une vérification serait perçue comme un manque de confiance.", 0, 1, 0, 0],
                    ['c', "Vérification du CV et des références, mais pas du casier pour les postes sensibles.", 60, 0, 1, 0],
                    ['d', "Aucune vérification des antécédents.", 0, 0, 0, 0],
                    ['e', "Autre.", 0, 0, 0, 1],
                ],
            ],

            'A.6.2' => [
                "Les termes et conditions de sécurité sont-ils intégrés aux contrats de travail et actés par les employés ?",
                "Inclut engagements de confidentialité, responsabilités sécurité, sanctions.",
                [
                    ['a', "Oui — clauses sécurité dans tous les contrats, signées par chaque employé à l'embauche.", 100, 0, 0, 0],
                    ['b', "La confiance mutuelle est notre valeur clé — nous n'avons pas besoin de clauses contractuelles.", 0, 1, 0, 0],
                    ['c', "NDA séparé signé, mais les responsabilités sécurité ne sont pas dans le contrat de travail.", 60, 0, 1, 0],
                    ['d', "Aucune clause sécurité dans les contrats.", 0, 0, 0, 0],
                    ['e', "Autre.", 0, 0, 0, 1],
                ],
            ],

            'A.6.3' => [
                "Une sensibilisation, formation et éducation à la sécurité sont-elles dispensées à tous les employés ?",
                "Formation obligatoire à l'embauche, sensibilisation régulière, adaptation au rôle.",
                [
                    ['a', "Oui — formation sécurité à l'embauche + campagnes annuelles (phishing simulé, e-learning).", 100, 0, 0, 0],
                    ['b', "Nous envoyons un email de rappel sécurité chaque année — c'est notre programme de sensibilisation.", 30, 1, 0, 0],
                    ['c', "Formation dispensée à l'embauche uniquement, sans renouvellement.", 60, 0, 1, 0],
                    ['d', "Pas de programme de sensibilisation.", 0, 0, 0, 0],
                    ['e', "Autre.", 0, 0, 0, 1],
                ],
            ],

            'A.6.4' => [
                "Un processus disciplinaire formel est-il en place pour les violations de la politique de sécurité ?",
                "Le processus doit être équitable, documenté et connu des employés.",
                [
                    ['a', "Oui — processus disciplinaire documenté, échelle de sanctions proportionnelles, connu des RH et managers.", 100, 0, 0, 0],
                    ['b', "Les violations sont gérées au cas par cas — la rigidité nuit à la culture d'entreprise.", 30, 1, 0, 0],
                    ['c', "Processus existant dans le règlement intérieur mais jamais communiqué spécifiquement.", 60, 0, 1, 0],
                    ['d', "Aucun processus disciplinaire lié à la sécurité.", 0, 0, 0, 0],
                    ['e', "Autre.", 0, 0, 0, 1],
                ],
            ],

            'A.6.5' => [
                "Les responsabilités sécurité des employés sont-elles maintenues après la fin du contrat ?",
                "Confidentialité et obligations post-contractuelles doivent être explicitement stipulées.",
                [
                    ['a', "Oui — clauses de confidentialité post-contractuelles dans tous les contrats, durée définie.", 100, 0, 0, 0],
                    ['b', "Une fois parti, l'employé n'a plus d'accès — c'est suffisant.", 0, 1, 0, 0],
                    ['c', "NDA post-embauche signé pour les profils sensibles uniquement.", 60, 0, 1, 0],
                    ['d', "Aucune clause post-contractuelle.", 0, 0, 0, 0],
                    ['e', "Autre.", 0, 0, 0, 1],
                ],
            ],

            'A.6.6' => [
                "Des accords de confidentialité (NDA) sont-ils conclus avec le personnel et les tiers ayant accès aux informations sensibles ?",
                "NDA adaptés au contexte, avec durée de validité et sanctions.",
                [
                    ['a', "Oui — NDA systématiques pour personnel interne et prestataires, revus juridiquement.", 100, 0, 0, 0],
                    ['b', "Nos employés signent le règlement intérieur qui mentionne la confidentialité — c'est équivalent.", 30, 1, 0, 0],
                    ['c', "NDA avec prestataires mais pas avec tous les employés internes.", 60, 0, 1, 0],
                    ['d', "Pas de NDA formalisé.", 0, 0, 0, 0],
                    ['e', "Autre.", 0, 0, 0, 1],
                ],
            ],

            'A.6.7' => [
                "Les employés travaillant à distance (télétravail) appliquent-ils les règles de sécurité adaptées ?",
                "Politique télétravail : VPN, chiffrement disque, verrouillage écran, réseau sécurisé.",
                [
                    ['a', "Oui — politique télétravail documentée, VPN obligatoire, formations spécifiques.", 100, 0, 0, 0],
                    ['b', "Nos employés sont responsables — nous leur faisons confiance pour sécuriser leur environnement.", 0, 1, 0, 0],
                    ['c', "VPN déployé mais pas de politique formelle sur l'usage des réseaux publics.", 60, 0, 1, 0],
                    ['d', "Aucune mesure spécifique pour le télétravail.", 0, 0, 0, 0],
                    ['e', "Autre.", 0, 0, 0, 1],
                ],
            ],

            'A.6.8' => [
                "Les employés peuvent-ils signaler les événements de sécurité par un canal dédié et accessible ?",
                "Canal de signalement connu, accessible, sans crainte de répercussions.",
                [
                    ['a', "Oui — canal dédié (email, outil ITSM), procédure connue, aucune sanction pour les signalements de bonne foi.", 100, 0, 0, 0],
                    ['b', "Les employés signalent à leur manager qui remonte si nécessaire.", 30, 1, 0, 0],
                    ['c', "Canal existant mais peu connu des employés.", 60, 0, 1, 0],
                    ['d', "Pas de canal de signalement dédié.", 0, 0, 0, 0],
                    ['e', "Autre.", 0, 0, 0, 1],
                ],
            ],

            // ─── A.7 Physical controls ────────────────────────────────────────

            'A.7.1' => [
                "Des périmètres de sécurité physique (zones sécurisées) sont-ils définis et protégés ?",
                "Barrières physiques, clôtures, portes sécurisées, contrôle d'accès.",
                [
                    ['a', "Oui — périmètres définis, accès contrôlé par badge, surveillance vidéo, visiteurs accompagnés.", 100, 0, 0, 0],
                    ['b', "Nos bureaux sont dans un immeuble gardienné — la sécurité physique est gérée par le bailleur.", 30, 1, 0, 0],
                    ['c', "Accès par badge au bâtiment, mais salle serveurs non spécifiquement sécurisée.", 60, 0, 1, 0],
                    ['d', "Aucun contrôle physique formalisé.", 0, 0, 0, 0],
                    ['e', "Autre.", 0, 0, 0, 1],
                ],
            ],

            'A.7.2' => [
                "L'accès physique aux zones sensibles est-il contrôlé et journalisé ?",
                "Contrôle d'accès différencié par zone, journaux d'entrée/sortie conservés.",
                [
                    ['a', "Oui — badges nominatifs, journaux d'accès conservés 90 jours min, revues régulières.", 100, 0, 0, 0],
                    ['b', "L'accès physique est limité aux employés — les badges sont anonymes mais c'est suffisant.", 30, 1, 0, 0],
                    ['c', "Contrôle d'accès en place mais journaux non conservés systématiquement.", 60, 0, 1, 0],
                    ['d', "Aucun contrôle d'accès physique aux zones sensibles.", 0, 0, 0, 0],
                    ['e', "Autre.", 0, 0, 0, 1],
                ],
            ],

            'A.7.3' => [
                "Les bureaux, salles et équipements sont-ils sécurisés physiquement ?",
                "Verrouillage, clean desk, protection contre observation (shoulder surfing).",
                [
                    ['a', "Oui — politique clean desk, écrans orientés, armoires verrouillées, portes fermées à clé.", 100, 0, 0, 0],
                    ['b', "Nos bureaux sont en open space — tout le monde peut voir ce que tout le monde fait.", 0, 1, 0, 0],
                    ['c', "Politique définie mais non contrôlée (pas d'audit clean desk).", 60, 0, 1, 0],
                    ['d', "Aucune mesure de sécurité physique des bureaux.", 0, 0, 0, 0],
                    ['e', "Autre.", 0, 0, 0, 1],
                ],
            ],

            'A.7.4' => [
                "La surveillance physique des locaux est-elle assurée (CCTV, gardiennage, alarmes) ?",
                "Surveillance adaptée aux risques, enregistrements conservés.",
                [
                    ['a', "Oui — CCTV avec enregistrement 30 jours, alarme intrusion, gardiennage ou téléportier.", 100, 0, 0, 0],
                    ['b', "La caméra dans le couloir d'entrée est suffisante pour notre taille.", 30, 1, 0, 0],
                    ['c', "Alarme intrusion en place, mais pas de CCTV ni journalisation des accès.", 60, 0, 1, 0],
                    ['d', "Aucun dispositif de surveillance physique.", 0, 0, 0, 0],
                    ['e', "Autre.", 0, 0, 0, 1],
                ],
            ],

            'A.7.5' => [
                "L'organisation est-elle protégée contre les menaces physiques et environnementales (incendie, inondation, etc.) ?",
                "Détection incendie, extinction, protection contre l'eau, alimentation de secours.",
                [
                    ['a', "Oui — détecteurs incendie, extinction automatique, protection inondation, onduleurs/groupe électrogène.", 100, 0, 0, 0],
                    ['b', "Le bâtiment est récent et aux normes — les risques physiques sont donc inexistants.", 0, 1, 0, 0],
                    ['c', "Détecteurs incendie présents, mais pas d'alimentation de secours pour la salle serveurs.", 60, 0, 1, 0],
                    ['d', "Aucune protection contre les menaces environnementales.", 0, 0, 0, 0],
                    ['e', "Autre.", 0, 0, 0, 1],
                ],
            ],

            'A.7.6' => [
                "L'organisation protège-t-elle contre les menaces externes et environnementales imprévues ?",
                "Plans d'urgence, procédures d'évacuation, contact services d'urgence.",
                [
                    ['a', "Oui — plan d'urgence documenté, exercices d'évacuation, contacts d'urgence affichés.", 100, 0, 0, 0],
                    ['b', "Les extincteurs sont présents et le plan d'évacuation est affiché — c'est conforme.", 30, 1, 0, 0],
                    ['c', "Procédures d'évacuation à jour mais pas de plan de reprise activités documenté.", 60, 0, 1, 0],
                    ['d', "Aucun plan d'urgence.", 0, 0, 0, 0],
                    ['e', "Autre.", 0, 0, 0, 1],
                ],
            ],

            'A.7.7' => [
                "La politique « bureau propre » (clean desk) et « écran propre » (clear screen) est-elle appliquée ?",
                "Documents sensibles rangés, écrans verrouillés en absence, imprimantes vidées.",
                [
                    ['a', "Oui — politique documentée, contrôles ponctuels (random checks), résultats tracés.", 100, 0, 0, 0],
                    ['b', "Les employés rangent naturellement leurs affaires — aucune politique formelle n'est nécessaire.", 0, 1, 0, 0],
                    ['c', "Politique existante mais non contrôlée.", 60, 0, 1, 0],
                    ['d', "Aucune politique clean desk / clear screen.", 0, 0, 0, 0],
                    ['e', "Autre.", 0, 0, 0, 1],
                ],
            ],

            'A.7.8' => [
                "Les équipements informatiques sont-ils placés et protégés pour réduire les risques d'accès non autorisé et de dommages ?",
                "Positionnement des écrans, verrous physiques, câbles anti-vol.",
                [
                    ['a', "Oui — serveurs en salle dédiée verrouillée, postes sécurisés physiquement, câbles anti-vol.", 100, 0, 0, 0],
                    ['b', "Les équipements sont dans les bureaux sécurisés — c'est suffisant.", 30, 1, 0, 0],
                    ['c', "Salle serveurs sécurisée mais postes de travail sans protection physique.", 60, 0, 1, 0],
                    ['d', "Aucune mesure de protection physique des équipements.", 0, 0, 0, 0],
                    ['e', "Autre.", 0, 0, 0, 1],
                ],
            ],

            'A.7.9' => [
                "Les actifs hors site (laptops, téléphones, clés USB) sont-ils protégés lorsqu'ils quittent les locaux ?",
                "Chiffrement des appareils mobiles, politique d'utilisation hors site.",
                [
                    ['a', "Oui — chiffrement disque complet (BitLocker/FileVault), MDM actif, politique hors site signée.", 100, 0, 0, 0],
                    ['b', "Nos laptops ont un mot de passe Windows — c'est suffisant pour les protéger.", 0, 1, 0, 0],
                    ['c', "Chiffrement sur les laptops, mais clés USB non contrôlées.", 60, 0, 1, 0],
                    ['d', "Aucune mesure de protection des actifs hors site.", 0, 0, 0, 0],
                    ['e', "Autre.", 0, 0, 0, 1],
                ],
            ],

            'A.7.10' => [
                "Les supports de stockage (USB, disques externes, CD) sont-ils gérés, contrôlés et sécurisés ?",
                "Inventaire, chiffrement, destruction sécurisée, contrôle des imports/exports.",
                [
                    ['a', "Oui — USB bloqués par défaut (GPO), supports chiffrés, destruction certifiée.", 100, 0, 0, 0],
                    ['b', "Nous avons interdit verbalement l'utilisation des clés USB.", 30, 1, 0, 0],
                    ['c', "Politique définie mais pas appliquée techniquement (pas de blocage USB).", 60, 0, 1, 0],
                    ['d', "Aucune gestion des supports de stockage amovibles.", 0, 0, 0, 0],
                    ['e', "Autre.", 0, 0, 0, 1],
                ],
            ],

            'A.7.11' => [
                "Les équipements bénéficient-ils d'une alimentation électrique et d'un câblage réseau sécurisés ?",
                "Onduleurs, redondance d'alimentation, câblage protégé contre les interférences.",
                [
                    ['a', "Oui — onduleurs UPS en place, câblage en conduits sécurisés, redondance électrique pour systèmes critiques.", 100, 0, 0, 0],
                    ['b', "Le fournisseur d'hébergement gère l'alimentation de nos serveurs — c'est leur responsabilité.", 30, 1, 0, 0],
                    ['c', "UPS en place pour les serveurs mais pas pour les postes utilisateurs critiques.", 60, 0, 1, 0],
                    ['d', "Aucune protection de l'alimentation électrique.", 0, 0, 0, 0],
                    ['e', "Autre.", 0, 0, 0, 1],
                ],
            ],

            'A.7.12' => [
                "La sécurité du câblage (réseau, énergie) est-elle assurée contre interception et dommages ?",
                "Câbles en conduits, étiquetés, protégés physiquement.",
                [
                    ['a', "Oui — câblage structuré en conduits fermés, étiquetage complet, documentation à jour.", 100, 0, 0, 0],
                    ['b', "Les câbles courent sous les faux-planchers — personne ne peut y accéder facilement.", 30, 1, 0, 0],
                    ['c', "Câblage structuré en salle serveurs, mais câbles réseau non protégés dans les bureaux.", 60, 0, 1, 0],
                    ['d', "Aucune mesure de sécurité du câblage.", 0, 0, 0, 0],
                    ['e', "Autre.", 0, 0, 0, 1],
                ],
            ],

            'A.7.13' => [
                "La maintenance des équipements est-elle réalisée selon les préconisations et journalisée ?",
                "Maintenance planifiée, interventions tracées, équipements vérifiés avant remise en service.",
                [
                    ['a', "Oui — contrats de maintenance à jour, interventions journalisées, vérification avant remise en service.", 100, 0, 0, 0],
                    ['b', "On intervient sur les équipements quand ils tombent en panne — la maintenance préventive n'est pas rentable.", 0, 1, 0, 0],
                    ['c', "Maintenance planifiée pour les serveurs, mais pas pour les postes utilisateurs.", 60, 0, 1, 0],
                    ['d', "Aucune politique de maintenance formalisée.", 0, 0, 0, 0],
                    ['e', "Autre.", 0, 0, 0, 1],
                ],
            ],

            'A.7.14' => [
                "Les équipements mis au rebut ou réutilisés font-ils l'objet d'un effacement sécurisé des données ?",
                "Effacement certifié ou destruction physique, pour tous les supports.",
                [
                    ['a', "Oui — effacement certifié (DBAN, Blancco) ou destruction physique, certificats conservés.", 100, 0, 0, 0],
                    ['b', "On formate les disques avant de les jeter ou revendre — le formatage est suffisant.", 0, 1, 0, 0],
                    ['c', "Effacement sécurisé pour les serveurs, mais pas systématique pour les postes utilisateurs.", 60, 0, 1, 0],
                    ['d', "Aucune procédure de mise au rebut sécurisée.", 0, 0, 0, 0],
                    ['e', "Autre.", 0, 0, 0, 1],
                ],
            ],

            // ─── A.8 Technological controls (sample — 34 controls) ────────────

            'A.8.1' => [
                "Les équipements des utilisateurs (PC, mobiles) sont-ils gérés et sécurisés par une politique BYOD ou MDM ?",
                "Gestion centralisée, chiffrement, effacement à distance.",
                [
                    ['a', "Oui — MDM déployé sur tous les appareils (entreprise et BYOD), chiffrement et effacement à distance actifs.", 100, 0, 0, 0],
                    ['b', "Nos employés connaissent les règles — un MDM serait intrusif dans leur vie privée.", 0, 1, 0, 0],
                    ['c', "MDM sur les appareils entreprise, mais BYOD non géré.", 60, 0, 1, 0],
                    ['d', "Aucune gestion centralisée des appareils.", 0, 0, 0, 0],
                    ['e', "Autre.", 0, 0, 0, 1],
                ],
            ],

            'A.8.2' => [
                "Les droits d'accès privilégiés sont-ils contrôlés, limités et journalisés (comptes admin) ?",
                "PAM (Privileged Access Management), rotation des mots de passe admin, journalisation des sessions.",
                [
                    ['a', "Oui — PAM ou vault en place, comptes admin nominatifs, sessions journalisées, rotation régulière.", 100, 0, 0, 0],
                    ['b', "Les administrateurs partagent un compte admin commun — c'est plus pratique et efficace.", 0, 1, 0, 0],
                    ['c', "Comptes admin nominatifs mais sessions non journalisées.", 60, 0, 1, 0],
                    ['d', "Aucun contrôle des accès privilégiés.", 0, 0, 0, 0],
                    ['e', "Autre.", 0, 0, 0, 1],
                ],
            ],

            'A.8.3' => [
                "L'accès aux informations est-il restreint conformément à la politique de contrôle d'accès ?",
                "Moindre privilège appliqué : ni trop, ni trop peu.",
                [
                    ['a', "Oui — accès basés sur les rôles (RBAC), revus régulièrement, moindre privilège effectif.", 100, 0, 0, 0],
                    ['b', "Pour travailler efficacement, nos équipes ont besoin d'un accès large aux données.", 0, 1, 0, 0],
                    ['c', "RBAC en place mais pas revu depuis plus d'un an.", 60, 0, 1, 0],
                    ['d', "Aucune restriction d'accès formelle.", 0, 0, 0, 0],
                    ['e', "Autre.", 0, 0, 0, 1],
                ],
            ],

            'A.8.4' => [
                "L'accès au code source et aux outils de développement est-il restreint et contrôlé ?",
                "Séparation prod/dev, accès en lecture seule pour la plupart, revues de code.",
                [
                    ['a', "Oui — dépôts Git protégés par branches, accès restreints, revues de code obligatoires.", 100, 0, 0, 0],
                    ['b', "Tout notre équipe dev a accès à tout le code — la collaboration l'exige.", 30, 1, 0, 0],
                    ['c', "Accès restreint au code prod, mais environnements de dev non segmentés.", 60, 0, 1, 0],
                    ['d', "Aucun contrôle d'accès au code source.", 0, 0, 0, 0],
                    ['e', "Autre.", 0, 0, 0, 1],
                ],
            ],

            'A.8.5' => [
                "L'authentification sécurisée (MFA, SSO) est-elle mise en œuvre sur les systèmes critiques ?",
                "MFA obligatoire pour les accès distants, VPN, webmails, interfaces d'administration.",
                [
                    ['a', "Oui — MFA déployé sur VPN, messagerie, interfaces admin et tous les accès distants.", 100, 0, 0, 0],
                    ['b', "Nos mots de passe complexes sont suffisants — le MFA est contraignant pour les utilisateurs.", 0, 1, 0, 0],
                    ['c', "MFA sur le VPN uniquement, pas sur la messagerie ni les interfaces admin.", 60, 0, 1, 0],
                    ['d', "Pas de MFA déployé.", 0, 0, 0, 0],
                    ['e', "Autre.", 0, 0, 0, 1],
                ],
            ],

            'A.8.6' => [
                "La capacité et les performances des systèmes sont-elles surveillées et gérées proactivement ?",
                "Monitoring de l'utilisation CPU, mémoire, stockage, avec alertes et planification.",
                [
                    ['a', "Oui — outil de monitoring (Zabbix, Datadog…), seuils d'alerte configurés, capacity planning annuel.", 100, 0, 0, 0],
                    ['b', "On est alerté par les utilisateurs quand les systèmes sont lents — c'est notre monitoring.", 0, 1, 0, 0],
                    ['c', "Monitoring en place mais sans alertes proactives ni capacity planning.", 60, 0, 1, 0],
                    ['d', "Aucune surveillance des capacités.", 0, 0, 0, 0],
                    ['e', "Autre.", 0, 0, 0, 1],
                ],
            ],

            'A.8.7' => [
                "Des protections contre les malwares sont-elles en place et maintenues à jour ?",
                "Antivirus/EDR sur tous les endpoints, mises à jour automatiques, scans planifiés.",
                [
                    ['a', "Oui — EDR déployé sur tous les postes et serveurs, signatures mises à jour automatiquement, scans hebdomadaires.", 100, 0, 0, 0],
                    ['b', "Windows Defender est inclus dans Windows — c'est suffisant pour nos besoins.", 30, 1, 0, 0],
                    ['c', "Antivirus déployé sur les postes mais pas sur les serveurs.", 60, 0, 1, 0],
                    ['d', "Aucune protection anti-malware.", 0, 0, 0, 0],
                    ['e', "Autre.", 0, 0, 0, 1],
                ],
            ],

            'A.8.8' => [
                "Les vulnérabilités techniques sont-elles identifiées, évaluées et corrigées dans des délais définis ?",
                "Processus de gestion des vulnérabilités : scan, priorisation CVSS, délai de patch défini.",
                [
                    ['a', "Oui — scans de vulnérabilités mensuels, SLA de patch selon CVSS (critique 24h, élevé 7j…).", 100, 0, 0, 0],
                    ['b', "Nous appliquons les mises à jour Windows chaque mois — la gestion des vulnérabilités est couverte.", 30, 1, 0, 0],
                    ['c', "Scans réalisés ponctuellement, sans SLA de correction défini.", 60, 0, 1, 0],
                    ['d', "Aucune gestion formelle des vulnérabilités.", 0, 0, 0, 0],
                    ['e', "Autre.", 0, 0, 0, 1],
                ],
            ],

            'A.8.9' => [
                "La gestion de la configuration des systèmes est-elle formalisée (baseline de sécurité) ?",
                "Configuration hardening, CIS Benchmarks ou équivalent, gestion des changements de config.",
                [
                    ['a', "Oui — baselines CIS ou internes documentées, gestion via CMDB ou IaC, changements tracés.", 100, 0, 0, 0],
                    ['b', "Les systèmes sont configurés par nos admins expérimentés — pas besoin de standards formels.", 0, 1, 0, 0],
                    ['c', "Baselines définies mais non vérifiées automatiquement (drift non détecté).", 60, 0, 1, 0],
                    ['d', "Aucune gestion de la configuration.", 0, 0, 0, 0],
                    ['e', "Autre.", 0, 0, 0, 1],
                ],
            ],

            'A.8.10' => [
                "La suppression ou l'effacement de l'information est-il géré de manière sécurisée tout au long du cycle de vie ?",
                "Politique de rétention, effacement sécurisé en fin de vie, pour données et systèmes.",
                [
                    ['a', "Oui — politique de rétention documentée, effacement sécurisé certifié, applicable aux systèmes cloud.", 100, 0, 0, 0],
                    ['b', "On supprime les fichiers dans la corbeille et on la vide — les données sont effacées.", 0, 1, 0, 0],
                    ['c', "Politique de rétention définie mais effacement sécurisé non systématique.", 60, 0, 1, 0],
                    ['d', "Aucune politique de suppression sécurisée.", 0, 0, 0, 0],
                    ['e', "Autre.", 0, 0, 0, 1],
                ],
            ],

            'A.8.11' => [
                "Le masquage des données (pseudonymisation, anonymisation) est-il appliqué pour protéger les données sensibles ?",
                "Données de test, rapports, exports — les données réelles ne doivent pas être exposées.",
                [
                    ['a', "Oui — masquage/pseudonymisation en environnements de test, tokenisation pour données sensibles en prod.", 100, 0, 0, 0],
                    ['b', "Nous utilisons des données réelles en recette — c'est le seul moyen de tester correctement.", 0, 1, 0, 0],
                    ['c', "Masquage appliqué pour certains projets mais pas systématisé.", 60, 0, 1, 0],
                    ['d', "Aucun masquage de données.", 0, 0, 0, 0],
                    ['e', "Autre.", 0, 0, 0, 1],
                ],
            ],

            'A.8.12' => [
                "Des mesures de prévention des fuites de données (DLP) sont-elles en place ?",
                "DLP email, endpoint, cloud ; politiques définies pour les données sensibles.",
                [
                    ['a', "Oui — solution DLP déployée couvrant email, endpoint et cloud, politiques testées.", 100, 0, 0, 0],
                    ['b', "Nos employés ne feraient pas fuiter des données volontairement.", 0, 1, 0, 0],
                    ['c', "Filtrage email en place mais pas de DLP endpoint ni cloud.", 60, 0, 1, 0],
                    ['d', "Aucune mesure DLP.", 0, 0, 0, 0],
                    ['e', "Autre.", 0, 0, 0, 1],
                ],
            ],

            'A.8.13' => [
                "Des sauvegardes régulières des informations sont-elles réalisées, testées et conservées de manière sécurisée ?",
                "Règle 3-2-1, tests de restauration, sauvegarde hors site.",
                [
                    ['a', "Oui — sauvegardes quotidiennes (3-2-1), tests de restauration mensuels, sauvegarde hors site chiffrée.", 100, 0, 0, 0],
                    ['b', "Nos sauvegardes sont automatiques sur le NAS local — on n'a jamais eu de problème.", 30, 1, 0, 0],
                    ['c', "Sauvegardes réalisées mais tests de restauration jamais effectués.", 60, 0, 1, 0],
                    ['d', "Aucune sauvegarde formalisée.", 0, 0, 0, 0],
                    ['e', "Autre.", 0, 0, 0, 1],
                ],
            ],

            'A.8.14' => [
                "La redondance des ressources informatiques est-elle assurée pour répondre aux exigences de disponibilité ?",
                "Haute disponibilité, clustering, failover, RTO/RPO.",
                [
                    ['a', "Oui — architecture HA documentée, failover testé, RTO/RPO atteints lors des tests.", 100, 0, 0, 0],
                    ['b', "Notre prestataire SaaS garantit 99.9% de disponibilité — la redondance n'est pas notre problème.", 30, 1, 0, 0],
                    ['c', "Redondance réseau en place, mais pas pour les serveurs applicatifs.", 60, 0, 1, 0],
                    ['d', "Aucune redondance mise en place.", 0, 0, 0, 0],
                    ['e', "Autre.", 0, 0, 0, 1],
                ],
            ],

            'A.8.15' => [
                "Des journaux d'événements (logs) sont-ils générés, protégés et analysés ?",
                "Logs système, applicatif, réseau ; SIEM ou agrégation centralisée ; conservation minimale définie.",
                [
                    ['a', "Oui — centralisation SIEM, logs intègres (immuables), rétention 12 mois min, alertes configurées.", 100, 0, 0, 0],
                    ['b', "Les logs sont sur chaque serveur — en cas d'incident on peut les consulter.", 30, 1, 0, 0],
                    ['c', "Logs centralisés mais conservation inférieure à 6 mois sans analyse proactive.", 60, 0, 1, 0],
                    ['d', "Pas de gestion formelle des journaux.", 0, 0, 0, 0],
                    ['e', "Autre.", 0, 0, 0, 1],
                ],
            ],

            'A.8.16' => [
                "Les activités réseau, systèmes et applications sont-elles surveillées pour détecter les comportements anormaux ?",
                "Détection d'anomalies, IDS/IPS, alertes sur comportements suspects.",
                [
                    ['a', "Oui — IDS/IPS actifs, détection comportementale (UEBA), alertes 24/7 ou SOC externalisé.", 100, 0, 0, 0],
                    ['b', "Notre pare-feu bloque les attaques connues — la surveillance continue n'est pas nécessaire.", 0, 1, 0, 0],
                    ['c', "Surveillance en place sur le réseau mais pas sur les endpoints ni les applications.", 60, 0, 1, 0],
                    ['d', "Aucune surveillance des activités.", 0, 0, 0, 0],
                    ['e', "Autre.", 0, 0, 0, 1],
                ],
            ],

            'A.8.17' => [
                "Les horloges de tous les systèmes sont-elles synchronisées sur une source de temps de référence (NTP) ?",
                "Synchronisation NTP obligatoire pour la corrélation des logs.",
                [
                    ['a', "Oui — NTP configuré sur tous les systèmes, synchronisé sur une source de référence fiable.", 100, 0, 0, 0],
                    ['b', "Windows se synchronise automatiquement — il n'y a rien à faire.", 30, 1, 0, 0],
                    ['c', "NTP configuré sur les serveurs mais pas sur tous les équipements réseau.", 60, 0, 1, 0],
                    ['d', "Pas de synchronisation horaire formalisée.", 0, 0, 0, 0],
                    ['e', "Autre.", 0, 0, 0, 1],
                ],
            ],

            'A.8.18' => [
                "L'utilisation des utilitaires système à privilèges élevés est-elle contrôlée et restreinte ?",
                "Outils d'administration, scripts, accès root — usage justifié et journalisé.",
                [
                    ['a', "Oui — liste blanche des outils autorisés, usage journalisé, revue périodique.", 100, 0, 0, 0],
                    ['b', "Les administrateurs système ont naturellement accès à tous les outils — c'est leur métier.", 0, 1, 0, 0],
                    ['c', "Contrôle en place pour les outils critiques, mais outils de scripting non restreints.", 60, 0, 1, 0],
                    ['d', "Aucun contrôle des utilitaires système.", 0, 0, 0, 0],
                    ['e', "Autre.", 0, 0, 0, 1],
                ],
            ],

            'A.8.19' => [
                "L'installation de logiciels sur les systèmes est-elle contrôlée et restreinte aux logiciels autorisés ?",
                "Politique d'utilisation des logiciels, liste blanche, contrôle des privilèges d'installation.",
                [
                    ['a', "Oui — installation verrouillée sur les postes, liste blanche centralisée, dérogations tracées.", 100, 0, 0, 0],
                    ['b', "Les employés sont libres d'installer ce dont ils ont besoin — ça favorise leur productivité.", 0, 1, 0, 0],
                    ['c', "Restriction définie mais non appliquée techniquement.", 60, 0, 1, 0],
                    ['d', "Aucun contrôle des installations logicielles.", 0, 0, 0, 0],
                    ['e', "Autre.", 0, 0, 0, 1],
                ],
            ],

            'A.8.20' => [
                "La sécurité des réseaux (segmentation, pare-feu, monitoring) est-elle gérée et documentée ?",
                "Architecture réseau documentée, règles de filtrage auditées, segmentation des zones.",
                [
                    ['a', "Oui — architecture réseau documentée, segmentation DMZ/LAN/OT, règles pare-feu revues annuellement.", 100, 0, 0, 0],
                    ['b', "Notre pare-feu de frontière filtre les connexions entrantes — le reste du réseau est de confiance.", 0, 1, 0, 0],
                    ['c', "Segmentation partielle, règles pare-feu non revues depuis plus d'un an.", 60, 0, 1, 0],
                    ['d', "Aucune gestion formelle de la sécurité réseau.", 0, 0, 0, 0],
                    ['e', "Autre.", 0, 0, 0, 1],
                ],
            ],

            'A.8.21' => [
                "La sécurité des services réseau (DNS, DHCP, NTP, VPN) est-elle assurée ?",
                "Services critiques sécurisés, redondants, configurés selon les bonnes pratiques.",
                [
                    ['a', "Oui — services réseau sécurisés (DNSSEC, VPN mutualisé), redondants et documentés.", 100, 0, 0, 0],
                    ['b', "Nous utilisons le DNS de notre FAI — c'est leur responsabilité de le sécuriser.", 0, 1, 0, 0],
                    ['c', "VPN sécurisé, mais DNS et DHCP non durcis.", 60, 0, 1, 0],
                    ['d', "Aucune sécurisation des services réseau.", 0, 0, 0, 0],
                    ['e', "Autre.", 0, 0, 0, 1],
                ],
            ],

            'A.8.22' => [
                "Les réseaux sont-ils segmentés pour réduire la surface d'attaque (microsegmentation, VLAN) ?",
                "Séparation des réseaux utilisateurs, serveurs, IoT, invités, DMZ.",
                [
                    ['a', "Oui — segmentation VLAN par usage, microsegmentation sur les zones critiques, matrice de flux documentée.", 100, 0, 0, 0],
                    ['b', "La segmentation est trop complexe pour notre taille — un seul réseau bien géré suffit.", 0, 1, 0, 0],
                    ['c', "VLAN utilisateurs/serveurs, mais IoT et Wi-Fi invités non séparés.", 60, 0, 1, 0],
                    ['d', "Pas de segmentation réseau.", 0, 0, 0, 0],
                    ['e', "Autre.", 0, 0, 0, 1],
                ],
            ],

            'A.8.23' => [
                "L'accès aux sites web externes est-il filtré et contrôlé (proxy web, filtrage de contenu) ?",
                "Filtrage URL, contrôle des téléchargements, inspection HTTPS.",
                [
                    ['a', "Oui — proxy web avec filtrage URL, inspection SSL, catégories bloquées définies.", 100, 0, 0, 0],
                    ['b', "Nos employés sont adultes et responsables — le filtrage web est infantilisant.", 0, 1, 0, 0],
                    ['c', "Filtrage DNS basique (dnsfilter, NextDNS), mais pas d'inspection HTTPS.", 60, 0, 1, 0],
                    ['d', "Aucun filtrage web.", 0, 0, 0, 0],
                    ['e', "Autre.", 0, 0, 0, 1],
                ],
            ],

            'A.8.24' => [
                "La cryptographie est-elle utilisée de manière appropriée pour protéger les données (en transit et au repos) ?",
                "Algorithmes approuvés, gestion des clés, chiffrement des données sensibles.",
                [
                    ['a', "Oui — chiffrement TLS 1.2+ pour les transits, chiffrement AES-256 au repos, gestion des clés documentée.", 100, 0, 0, 0],
                    ['b', "Nos données sont dans le cloud du fournisseur qui les chiffre pour nous.", 30, 1, 0, 0],
                    ['c', "Chiffrement en transit (HTTPS), mais données au repos non chiffrées.", 60, 0, 1, 0],
                    ['d', "Aucun chiffrement en place.", 0, 0, 0, 0],
                    ['e', "Autre.", 0, 0, 0, 1],
                ],
            ],

            'A.8.25' => [
                "Un cycle de développement sécurisé (SSDLC) est-il appliqué pour les logiciels développés en interne ?",
                "Security by design, revue de code, tests SAST/DAST, OWASP Top 10.",
                [
                    ['a', "Oui — SSDLC documenté, SAST/DAST intégrés dans la CI/CD, revue de code obligatoire.", 100, 0, 0, 0],
                    ['b', "Notre équipe dev expérimentée connaît les bonnes pratiques — un processus formel ralentirait les livraisons.", 0, 1, 0, 0],
                    ['c', "Revue de code en place, mais SAST/DAST non intégrés.", 60, 0, 1, 0],
                    ['d', "Aucun processus de développement sécurisé.", 0, 0, 0, 0],
                    ['e', "Autre.", 0, 0, 0, 1],
                ],
            ],

            'A.8.26' => [
                "Les exigences de sécurité des applications sont-elles définies et vérifiées lors de leur acquisition ou développement ?",
                "Exigences sécurité dans les cahiers des charges, tests d'acceptation sécurité.",
                [
                    ['a', "Oui — exigences sécurité dans les specs, tests d'acceptation sécurité avant mise en prod.", 100, 0, 0, 0],
                    ['b', "On choisit des applications reconnues sur le marché — leur sécurité est présumée.", 0, 1, 0, 0],
                    ['c', "Exigences définies pour les projets majeurs, pas pour les achats logiciels standards.", 60, 0, 1, 0],
                    ['d', "Pas d'exigences sécurité dans les processus d'acquisition.", 0, 0, 0, 0],
                    ['e', "Autre.", 0, 0, 0, 1],
                ],
            ],

            'A.8.27' => [
                "Des principes d'architecture sécurisée sont-ils appliqués lors de la conception des systèmes ?",
                "Zero-trust, defense in depth, séparation des couches, résilience.",
                [
                    ['a', "Oui — principes zero-trust, defense in depth documentés et appliqués dans les conceptions.", 100, 0, 0, 0],
                    ['b', "Nos développeurs conçoivent les systèmes selon leurs connaissances et l'expérience acquise.", 30, 1, 0, 0],
                    ['c', "Principes connus mais non formalisés ni vérifiés lors des revues d'architecture.", 60, 0, 1, 0],
                    ['d', "Aucun principe d'architecture sécurisée.", 0, 0, 0, 0],
                    ['e', "Autre.", 0, 0, 0, 1],
                ],
            ],

            'A.8.28' => [
                "Le code sécurisé (secure coding) est-il pratiqué et vérifié dans vos développements ?",
                "Formation des développeurs, revues de code sécurité, outils SAST.",
                [
                    ['a', "Oui — formation OWASP des devs, revues de code sécurité, SAST dans la pipeline.", 100, 0, 0, 0],
                    ['b', "Le framework que nous utilisons protège automatiquement contre la plupart des vulnérabilités.", 30, 1, 0, 0],
                    ['c', "Revues de code fonctionnelles mais sans focus sécurité.", 60, 0, 1, 0],
                    ['d', "Aucune pratique de secure coding.", 0, 0, 0, 0],
                    ['e', "Autre.", 0, 0, 0, 1],
                ],
            ],

            'A.8.29' => [
                "Des tests de sécurité (pentest, DAST, bug bounty) sont-ils réalisés avant la mise en production ?",
                "Tests sur les nouvelles fonctionnalités et périodiquement sur les systèmes existants.",
                [
                    ['a', "Oui — pentest annuel par prestataire externe, DAST dans la CI/CD, résultats tracés.", 100, 0, 0, 0],
                    ['b', "Nos tests fonctionnels incluent des tests de non-régression — c'est suffisant.", 0, 1, 0, 0],
                    ['c', "Pentest réalisé à la création du système, jamais renouvelé.", 60, 0, 1, 0],
                    ['d', "Aucun test de sécurité.", 0, 0, 0, 0],
                    ['e', "Autre.", 0, 0, 0, 1],
                ],
            ],

            'A.8.30' => [
                "Le développement externalisé est-il supervisé et contrôlé par votre organisation ?",
                "Exigences contractuelles, code reviews, audits du code livré.",
                [
                    ['a', "Oui — clauses sécurité dans les contrats, revue du code livré, tests d'acceptation sécurité.", 100, 0, 0, 0],
                    ['b', "Notre prestataire de dev est reconnu sur le marché — nous lui faisons confiance.", 0, 1, 0, 0],
                    ['c', "Exigences sécurité dans le contrat mais pas de revue du code livré.", 60, 0, 1, 0],
                    ['d', "Aucun contrôle du développement externalisé.", 0, 0, 0, 0],
                    ['e', "Autre.", 0, 0, 0, 1],
                ],
            ],

            'A.8.31' => [
                "Les environnements de développement, test et production sont-ils séparés ?",
                "Pas de données réelles en dev/test, déploiements contrôlés.",
                [
                    ['a', "Oui — environnements strictement séparés, données de prod anonymisées pour les tests.", 100, 0, 0, 0],
                    ['b', "Nos développeurs testent directement en prod pour aller plus vite.", 0, 1, 0, 0],
                    ['c', "Environnements séparés, mais données réelles utilisées en recette.", 60, 0, 1, 0],
                    ['d', "Aucune séparation des environnements.", 0, 0, 0, 0],
                    ['e', "Autre.", 0, 0, 0, 1],
                ],
            ],

            'A.8.32' => [
                "La gestion des changements (change management) est-elle formalisée pour les systèmes d'information ?",
                "Processus d'approbation, tests avant déploiement, procédure de rollback.",
                [
                    ['a', "Oui — CAB (Change Advisory Board), fenêtres de maintenance, rollback documenté.", 100, 0, 0, 0],
                    ['b', "Les développeurs déploient directement en prod — nous avons les meilleurs profils.", 0, 1, 0, 0],
                    ['c', "Processus de change défini pour l'infra, mais pas pour les applications.", 60, 0, 1, 0],
                    ['d', "Aucune gestion des changements.", 0, 0, 0, 0],
                    ['e', "Autre.", 0, 0, 0, 1],
                ],
            ],

            'A.8.33' => [
                "Les informations relatives aux tests sont-elles sélectionnées, protégées et gérées de manière appropriée ?",
                "Données de test fictives ou anonymisées, gestion des accès aux environnements de test.",
                [
                    ['a', "Oui — génération de données de test synthétiques, accès aux environnements de test restreints.", 100, 0, 0, 0],
                    ['b', "Une copie de la prod est plus représentative pour tester — c'est notre pratique standard.", 0, 1, 0, 0],
                    ['c', "Données anonymisées pour les projets sensibles, mais pas systématiquement.", 60, 0, 1, 0],
                    ['d', "Aucune gestion spécifique des données de test.", 0, 0, 0, 0],
                    ['e', "Autre.", 0, 0, 0, 1],
                ],
            ],

            'A.8.34' => [
                "Des audits de systèmes d'information sont-ils planifiés et réalisés de manière à minimiser les perturbations ?",
                "Planification avec les équipes, accès en lecture seule pour l'auditeur, résultats documentés.",
                [
                    ['a', "Oui — audits planifiés, accès en lecture seule, rapport d'audit et plan d'action suivi.", 100, 0, 0, 0],
                    ['b', "Un audit peut se faire à tout moment — si nos systèmes sont bien gérés, il ne peut rien se passer.", 30, 1, 0, 0],
                    ['c', "Audits réalisés, mais sans plan d'action formalisé en sortie.", 60, 0, 1, 0],
                    ['d', "Aucun audit des systèmes d'information.", 0, 0, 0, 0],
                    ['e', "Autre.", 0, 0, 0, 1],
                ],
            ],
        ];
    }

    public function down()
    {
        $this->db->table('control_choices')->truncate();
        $this->db->table('control_questions')->truncate();
    }
}

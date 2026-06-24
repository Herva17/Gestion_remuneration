<?php

require_once __DIR__ . '/../Config/Database.php';
require_once __DIR__ . '/Agent.php';
require_once __DIR__ . '/Affectation.php';
require_once __DIR__ . '/avantages.php';
require_once __DIR__ . '/Retenue.php';
require_once __DIR__ . '/Avance.php';

class Remuneration {
    private $id;
    private $id_agent;
    private $id_affectation;
    private $date_remun;
    private $mois;
    private $annee;
    private $db;

    private $agent;
    private $affectation;
    private $avantages;
    private $retenues;
    private $avances;

    // Constantes pour les types d'avantages et retenues
    const TYPE_AVANTAGE = [
        'PRIME' => 'prime',
        'INDEMNITE' => 'indemnite',
        'BONUS' => 'bonus',
        'AUTRE' => 'autre'
    ];

    const TYPE_RETENUE = [
        'IMPOT' => 'impot',
        'SECURITE_SOCIALE' => 'securite_sociale',
        'ASSURANCE' => 'assurance',
        'PRET' => 'pret',
        'AUTRE' => 'autre'
    ];

    public function __construct(
        $id_agent = null,
        $id_affectation = null,
        $date_remun = null,
        $mois = null,
        $annee = null,
        $id = null
    ) {
        $this->id = $id;
        $this->id_agent = $id_agent;
        $this->id_affectation = $id_affectation;
        $this->date_remun = $date_remun;
        $this->mois = $mois;
        $this->annee = $annee;
        $this->db = Database::getInstance();
        
        // Initialisation des collections
        $this->avantages = [];
        $this->retenues = [];
        $this->avances = [];
    }

    // ========== GETTERS ET SETTERS ==========
    public function getId() { return $this->id; }
    public function getIdAgent() { return $this->id_agent; }
    public function getIdAffectation() { return $this->id_affectation; }
    public function getDateRemun() { return $this->date_remun; }
    public function getMois() { return $this->mois; }
    public function getAnnee() { return $this->annee; }

    public function setId($id) { $this->id = $id; return $this; }
    public function setIdAgent($id_agent) { $this->id_agent = $id_agent; return $this; }
    public function setIdAffectation($id_affectation) { $this->id_affectation = $id_affectation; return $this; }
    public function setDateRemun($date_remun) { $this->date_remun = $date_remun; return $this; }
    public function setMois($mois) { $this->mois = $mois; return $this; }
    public function setAnnee($annee) { $this->annee = $annee; return $this; }

    /**
     * Récupère le montant de base depuis l'affectation
     * @return float|null
     */
    public function getMontant() {
        if ($this->id_affectation) {
            $affectation = $this->getAffectation();
            if ($affectation) {
                return $affectation->getMontantRemunerer();
            }
        }
        return null;
    }

    /**
     * Alias pour getMontant() - compatibilité avec l'ancien code
     * @return float|null
     */
    public function getMontantBase() {
        return $this->getMontant();
    }

    // ========== MÉTHODES DE CHARGEMENT ==========

    /**
     * Charge l'affectation associée
     */
    private function loadAffectation() {
        if ($this->affectation === null && $this->id_affectation) {
            $this->affectation = Affectation::getById($this->id_affectation);
            if ($this->affectation && $this->id_agent === null) {
                $this->id_agent = $this->affectation->getIdAgent();
            }
        }
    }

    /**
     * Charge les avantages de l'agent pour la période
     */
    private function loadAvantages() {
        if (empty($this->avantages) && $this->id_agent) {
            $this->avantages = Avantage::getByAgentAndMonth(
                $this->id_agent, 
                $this->mois, 
                $this->annee
            );
        }
    }

    /**
     * Charge les retenues de l'agent pour la période
     */
    private function loadRetenues() {
        if (empty($this->retenues) && $this->id_agent) {
            $this->retenues = Retenue::getByAgentAndMonth(
                $this->id_agent, 
                $this->mois, 
                $this->annee
            );
        }
    }

    /**
     * Charge les avances de l'agent pour la période
     */
    private function loadAvances() {
        if (empty($this->avances) && $this->id_agent) {
            $this->avances = Avance::getByAgentAndMonth(
                $this->id_agent, 
                $this->mois, 
                $this->annee
            );
        }
    }

    // ========== MÉTHODES DE CALCUL ==========

    /**
     * Récupère l'affectation associée
     * @return Affectation|null
     */
    public function getAffectation() {
        $this->loadAffectation();
        return $this->affectation;
    }

    /**
     * Récupère tous les avantages de l'agent pour la période
     * @return array Liste des avantages
     */
    public function getAvantages() {
        $this->loadAvantages();
        return $this->avantages;
    }

    /**
     * Récupère toutes les retenues de l'agent pour la période
     * @return array Liste des retenues
     */
    public function getRetenues() {
        $this->loadRetenues();
        return $this->retenues;
    }

    /**
     * Récupère toutes les avances de l'agent pour la période
     * @return array Liste des avances
     */
    public function getAvances() {
        $this->loadAvances();
        return $this->avances;
    }

    /**
     * Calcule le total des avantages
     * @return float Total des avantages
     */
    public function getTotalAvantages() {
        $total = 0;
        foreach ($this->getAvantages() as $avantage) {
            $total += $avantage->getMontant();
        }
        return $total;
    }

    /**
     * Calcule le total des retenues
     * @return float Total des retenues
     */
    public function getTotalRetenues() {
        $total = 0;
        foreach ($this->getRetenues() as $retenue) {
            $total += $retenue->getMontant();
        }
        return $total;
    }

    /**
     * Calcule le total des avances (uniquement les avances en cours)
     * @return float Total des avances
     */
    public function getTotalAvances() {
        $total = 0;
        foreach ($this->getAvances() as $avance) {
            // Seules les avances en cours sont déduites
            if ($avance->isEnCours()) {
                $total += $avance->getMontant();
            }
        }
        return $total;
    }

    /**
     * Calcule le total de toutes les avances (y compris remboursées)
     * @return float Total de toutes les avances
     */
    public function getTotalAvancesAll() {
        $total = 0;
        foreach ($this->getAvances() as $avance) {
            $total += $avance->getMontant();
        }
        return $total;
    }

    /**
     * Récupère le total des avances en cours
     * @return float Total des avances en cours
     */
    public function getTotalAvancesEnCours() {
        $total = 0;
        foreach ($this->getAvances() as $avance) {
            if ($avance->isEnCours()) {
                $total += $avance->getMontant();
            }
        }
        return $total;
    }

    /**
     * Récupère le total des avances remboursées
     * @return float Total des avances remboursées
     */
    public function getTotalAvancesRemboursees() {
        $total = 0;
        foreach ($this->getAvances() as $avance) {
            if ($avance->isRembourse()) {
                $total += $avance->getMontant();
            }
        }
        return $total;
    }

    /**
     * Calcule le salaire brut (Salaire de base + Avantages)
     * @return float Salaire brut
     */
    public function getSalaireBrut() {
        $montant = $this->getMontant() ?? 0;
        return $montant + $this->getTotalAvantages();
    }

    /**
     * Calcule le salaire net (Salaire brut - Retenues)
     * @return float Salaire net
     */
    public function getSalaireNet() {
        return $this->getSalaireBrut() - $this->getTotalRetenues();
    }

    /**
     * Calcule le salaire net à payer (Salaire net - Avances en cours)
     * @return float Salaire net à payer
     */
    public function getSalaireNetAPayer() {
        $salaireNet = $this->getSalaireNet();
        $totalAvances = $this->getTotalAvances();
        $resultat = $salaireNet - $totalAvances;
        
        // Si le résultat est négatif, on retourne 0
        return $resultat > 0 ? $resultat : 0;
    }

    /**
     * Calcule le montant total déduit (Retenues + Avances)
     * @return float Total des déductions
     */
    public function getTotalDeductions() {
        return $this->getTotalRetenues() + $this->getTotalAvances();
    }

    // ========== MÉTHODES DE DÉTAIL ==========

    /**
     * Récupère les avantages par catégorie
     * @return array Avantages groupés par type
     */
    public function getAvantagesParCategorie() {
        $result = [];
        foreach ($this->getAvantages() as $avantage) {
            $type = $avantage->getType() ?? 'autre';
            if (!isset($result[$type])) {
                $result[$type] = [
                    'total' => 0,
                    'items' => []
                ];
            }
            $result[$type]['total'] += $avantage->getMontant();
            $result[$type]['items'][] = $avantage;
        }
        return $result;
    }

    /**
     * Récupère les retenues par catégorie
     * @return array Retenues groupées par type
     */
    public function getRetenuesParCategorie() {
        $result = [];
        foreach ($this->getRetenues() as $retenue) {
            $type = $retenue->getType() ?? 'autre';
            if (!isset($result[$type])) {
                $result[$type] = [
                    'total' => 0,
                    'items' => []
                ];
            }
            $result[$type]['total'] += $retenue->getMontant();
            $result[$type]['items'][] = $retenue;
        }
        return $result;
    }

    /**
     * Récupère les avances par catégorie (libellé)
     * @return array Avances groupées par libellé
     */
    public function getAvancesParCategorie() {
        $result = [];
        foreach ($this->getAvances() as $avance) {
            $libelle = $avance->getLibelle() ?? 'autre';
            if (!isset($result[$libelle])) {
                $result[$libelle] = [
                    'total' => 0,
                    'items' => [],
                    'en_cours' => 0,
                    'rembourse' => 0
                ];
            }
            $result[$libelle]['total'] += $avance->getMontant();
            $result[$libelle]['items'][] = $avance;
            if ($avance->isEnCours()) {
                $result[$libelle]['en_cours'] += $avance->getMontant();
            } elseif ($avance->isRembourse()) {
                $result[$libelle]['rembourse'] += $avance->getMontant();
            }
        }
        return $result;
    }

    /**
     * Récupère les avances par statut
     * @return array Avances groupées par statut
     */
    public function getAvancesParStatut() {
        $result = [
            'en_cours' => ['total' => 0, 'items' => []],
            'rembourse' => ['total' => 0, 'items' => []]
        ];
        foreach ($this->getAvances() as $avance) {
            if ($avance->isEnCours()) {
                $result['en_cours']['total'] += $avance->getMontant();
                $result['en_cours']['items'][] = $avance;
            } elseif ($avance->isRembourse()) {
                $result['rembourse']['total'] += $avance->getMontant();
                $result['rembourse']['items'][] = $avance;
            }
        }
        return $result;
    }

    // ========== MÉTHODES DE STATISTIQUES ==========

    /**
     * Obtient le résumé complet du salaire
     * @return array Résumé complet
     */
    public function getResumeSalaire() {
        $montant = $this->getMontant() ?? 0;
        $totalAvances = $this->getTotalAvances();
        $totalAvancesAll = $this->getTotalAvancesAll();
        $totalAvancesEnCours = $this->getTotalAvancesEnCours();
        $totalAvancesRemboursees = $this->getTotalAvancesRemboursees();
        $salaireNet = $this->getSalaireNet();
        $salaireNetAPayer = $this->getSalaireNetAPayer();

        return [
            'id_affectation' => $this->id_affectation,
            'salaire_base' => $montant,
            'total_avantages' => $this->getTotalAvantages(),
            'salaire_brut' => $this->getSalaireBrut(),
            'total_retenues' => $this->getTotalRetenues(),
            'salaire_net' => $salaireNet,
            'total_avances' => $totalAvances,
            'total_avances_all' => $totalAvancesAll,
            'total_avances_en_cours' => $totalAvancesEnCours,
            'total_avances_remboursees' => $totalAvancesRemboursees,
            'salaire_net_a_payer' => $salaireNetAPayer,
            'nb_avantages' => count($this->getAvantages()),
            'nb_retenues' => count($this->getRetenues()),
            'nb_avances' => count($this->getAvances()),
            'nb_avances_en_cours' => count(array_filter($this->getAvances(), function($a) { return $a->isEnCours(); })),
            'nb_avances_remboursees' => count(array_filter($this->getAvances(), function($a) { return $a->isRembourse(); })),
            'pourcentage_retenues' => $this->getPourcentageRetenues(),
            'pourcentage_avantages' => $this->getPourcentageAvantages(),
            'pourcentage_avances' => $this->getPourcentageAvances(),
            'mois' => $this->mois,
            'annee' => $this->annee,
            'total_deductions' => $this->getTotalDeductions()
        ];
    }

    /**
     * Calcule le pourcentage des retenues par rapport au salaire brut
     * @return float Pourcentage des retenues
     */
    public function getPourcentageRetenues() {
        $brut = $this->getSalaireBrut();
        if ($brut == 0) return 0;
        return ($this->getTotalRetenues() / $brut) * 100;
    }

    /**
     * Calcule le pourcentage des avantages par rapport au salaire de base
     * @return float Pourcentage des avantages
     */
    public function getPourcentageAvantages() {
        $montant = $this->getMontant() ?? 0;
        if ($montant == 0) return 0;
        return ($this->getTotalAvantages() / $montant) * 100;
    }

    /**
     * Calcule le pourcentage des avances par rapport au salaire net
     * @return float Pourcentage des avances
     */
    public function getPourcentageAvances() {
        $net = $this->getSalaireNet();
        if ($net == 0) return 0;
        return ($this->getTotalAvances() / $net) * 100;
    }

    /**
     * Vérifie si le salaire est positif
     * @return bool
     */
    public function isSalairePositif() {
        return $this->getSalaireNetAPayer() > 0;
    }

    /**
     * Vérifie si l'agent a des avances en cours
     * @return bool
     */
    public function hasAvancesEnCours() {
        return $this->getTotalAvancesEnCours() > 0;
    }

    /**
     * Vérifie si l'agent a des avances remboursées
     * @return bool
     */
    public function hasAvancesRemboursees() {
        return $this->getTotalAvancesRemboursees() > 0;
    }

    /**
     * Vérifie si une affectation est associée
     * @return bool
     */
    public function hasAffectation() {
        return $this->id_affectation !== null && $this->id_affectation > 0;
    }

    // ========== MÉTHODES D'EXPORT ==========

    /**
     * Exporte les données en format tableau associatif
     * @return array Données formatées
     */
    public function toArray() {
        $montant = $this->getMontant();
        return [
            'id' => $this->id,
            'id_agent' => $this->id_agent,
            'id_affectation' => $this->id_affectation,
            'agent' => $this->getAgent() ? $this->getAgent()->getNomComplet() : null,
            'affectation' => $this->getAffectation() ? [
                'id' => $this->getAffectation()->getId(),
                'lieu' => $this->getAffectation()->getLieuAffectation(),
                'montant' => $this->getAffectation()->getMontantRemunerer()
            ] : null,
            'mois' => $this->mois,
            'annee' => $this->annee,
            'salaire_base' => $montant,
            'avantages' => array_map(function($a) {
                return [
                    'id' => $a->getId(),
                    'libelle' => $a->getLibelle(),
                    'montant' => $a->getMontant(),
                    'type' => $a->getType()
                ];
            }, $this->getAvantages()),
            'retenues' => array_map(function($r) {
                return [
                    'id' => $r->getId(),
                    'libelle' => $r->getLibelle(),
                    'montant' => $r->getMontant(),
                    'type' => $r->getType()
                ];
            }, $this->getRetenues()),
            'avances' => array_map(function($a) {
                return [
                    'id' => $a->getId(),
                    'libelle' => $a->getLibelle(),
                    'montant' => $a->getMontant(),
                    'statut' => $a->getStatut(),
                    'statut_libelle' => $a->getStatutLibelle()
                ];
            }, $this->getAvances()),
            'total_avantages' => $this->getTotalAvantages(),
            'total_retenues' => $this->getTotalRetenues(),
            'total_avances' => $this->getTotalAvances(),
            'total_avances_all' => $this->getTotalAvancesAll(),
            'total_avances_en_cours' => $this->getTotalAvancesEnCours(),
            'total_avances_remboursees' => $this->getTotalAvancesRemboursees(),
            'salaire_brut' => $this->getSalaireBrut(),
            'salaire_net' => $this->getSalaireNet(),
            'salaire_net_a_payer' => $this->getSalaireNetAPayer(),
            'total_deductions' => $this->getTotalDeductions(),
            'has_avances_en_cours' => $this->hasAvancesEnCours(),
            'has_affectation' => $this->hasAffectation()
        ];
    }

    /**
     * Génère un récapitulatif pour l'affichage
     * @return string HTML du récapitulatif
     */
    public function toHtml() {
        $resume = $this->getResumeSalaire();
        $affectation = $this->getAffectation();
        $montant = $this->getMontant() ?? 0;
        
        $html = '<div class="recap-salaire">';
        $html .= '<h3>Récapitulatif du salaire - ' . $this->mois . ' ' . $this->annee . '</h3>';
        
        // Informations sur l'affectation
        if ($affectation) {
            $html .= '<div style="background:#eff6ff;padding:8px 12px;border-radius:6px;margin-bottom:12px;font-size:13px;">';
            $html .= '<strong>Affectation :</strong> ' . htmlspecialchars($affectation->getLieuAffectation() ?: 'Non spécifié');
            $html .= ' | <strong>Référence :</strong> #' . $affectation->getId();
            $html .= '</div>';
        }
        
        $html .= '<table class="table table-bordered">';
        $html .= '<tr><td>Salaire de base</td><td>' . number_format($montant, 2) . ' F</td></tr>';
        $html .= '<tr><td>Total avantages (' . $resume['nb_avantages'] . ')</td><td>' . number_format($resume['total_avantages'], 2) . ' F</td></tr>';
        $html .= '<tr class="total"><td><strong>Salaire brut</strong></td><td><strong>' . number_format($resume['salaire_brut'], 2) . ' F</strong></td></tr>';
        $html .= '<tr><td>Total retenues (' . $resume['nb_retenues'] . ')</td><td>' . number_format($resume['total_retenues'], 2) . ' F</td></tr>';
        $html .= '<tr class="total"><td><strong>Salaire net</strong></td><td><strong>' . number_format($resume['salaire_net'], 2) . ' F</strong></td></tr>';
        
        // Affichage des avances
        if ($resume['nb_avances'] > 0) {
            $html .= '<tr style="background:#fff3cd;">';
            $html .= '<td><strong>Total avances (' . $resume['nb_avances'] . ')</strong></td>';
            $html .= '<td><strong style="color:#f97316;">- ' . number_format($resume['total_avances'], 2) . ' F</strong></td>';
            $html .= '</tr>';
            
            // Détail des avances
            if ($resume['nb_avances_en_cours'] > 0) {
                $html .= '<tr><td style="font-size:12px;color:#94a3b8;">&nbsp;&nbsp;Avances en cours (' . $resume['nb_avances_en_cours'] . ')</td>';
                $html .= '<td style="font-size:12px;color:#f97316;">' . number_format($resume['total_avances_en_cours'], 2) . ' F</td></tr>';
            }
            if ($resume['nb_avances_remboursees'] > 0) {
                $html .= '<tr><td style="font-size:12px;color:#94a3b8;">&nbsp;&nbsp;Avances remboursées (' . $resume['nb_avances_remboursees'] . ')</td>';
                $html .= '<td style="font-size:12px;color:#16a34a;">' . number_format($resume['total_avances_remboursees'], 2) . ' F</td></tr>';
            }
        }
        
        $html .= '<tr class="total final" style="background:linear-gradient(135deg, #2563eb, #1d4ed8);color:white;">';
        $html .= '<td><strong>Salaire net à payer</strong></td>';
        $html .= '<td><strong>' . number_format($resume['salaire_net_a_payer'], 2) . ' F</strong></td>';
        $html .= '</tr>';
        
        if ($resume['total_avances'] > 0) {
            $html .= '<tr><td colspan="2" style="font-size:11px;color:#94a3b8;text-align:center;">';
            $html .= 'Formule : Salaire net - Avances = ' . number_format($resume['salaire_net'], 2) . ' - ' . number_format($resume['total_avances'], 2) . ' = ' . number_format($resume['salaire_net_a_payer'], 2);
            $html .= '</td></tr>';
        }
        
        $html .= '</table>';
        $html .= '</div>';
        return $html;
    }

    // ========== MÉTHODES STATIQUES UTILITAIRES ==========

    /**
     * Calcule le salaire pour un agent sur une période donnée
     * @param int $id_agent ID de l'agent
     * @param string $mois Mois
     * @param int $annee Année
     * @return array Résultat du calcul
     */
    public static function calculerSalaireAgent($id_agent, $mois, $annee) {
        $remuneration = self::getByAgentAndMonth($id_agent, $mois, $annee);
        if ($remuneration) {
            return $remuneration->getResumeSalaire();
        }
        return null;
    }

    /**
     * Récupère une rémunération par agent, mois et année
     * @param int $id_agent ID de l'agent
     * @param string $mois Mois
     * @param int $annee Année
     * @return self|null
     */
    public static function getByAgentAndMonth($id_agent, $mois, $annee) {
        $db = Database::getInstance();
        $sql = "SELECT * FROM remuneration WHERE id_agent = :id_agent AND mois = :mois AND annee = :annee";
        
        try {
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':id_agent', $id_agent);
            $stmt->bindParam(':mois', $mois);
            $stmt->bindParam(':annee', $annee);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                return new self(
                    $row['id_agent'],
                    $row['id_affectation'],
                    $row['date_remun'],
                    $row['mois'],
                    $row['annee'],
                    $row['id']
                );
            }
            return null;
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération de la rémunération : " . $e->getMessage());
            return null;
        }
    }

    /**
     * Récupère toutes les rémunérations d'un agent avec leurs calculs
     * @param int $id_agent ID de l'agent
     * @return array Liste des rémunérations avec calculs
     */
    public static function getByAgentWithCalculs($id_agent) {
        $remunerations = self::getByAgent($id_agent);
        $result = [];
        foreach ($remunerations as $remuneration) {
            $result[] = $remuneration->getResumeSalaire();
        }
        return $result;
    }

    /**
     * Récupère une rémunération par affectation
     * @param int $id_affectation ID de l'affectation
     * @return self|null
     */
    public static function getByAffectation($id_affectation) {
        $db = Database::getInstance();
        $sql = "SELECT * FROM remuneration WHERE id_affectation = :id_affectation";
        
        try {
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':id_affectation', $id_affectation);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                return new self(
                    $row['id_agent'],
                    $row['id_affectation'],
                    $row['date_remun'],
                    $row['mois'],
                    $row['annee'],
                    $row['id']
                );
            }
            return null;
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération de la rémunération : " . $e->getMessage());
            return null;
        }
    }

    // ========== MÉTHODES DE GESTION ==========

    public function insert() {
        if (empty($this->id_agent) || empty($this->id_affectation)) {
            return false;
        }

        $sql = "INSERT INTO remuneration (id_agent, id_affectation, date_remun, mois, annee) 
                VALUES (:id_agent, :id_affectation, :date_remun, :mois, :annee)";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id_agent', $this->id_agent);
            $stmt->bindParam(':id_affectation', $this->id_affectation);
            $stmt->bindParam(':date_remun', $this->date_remun);
            $stmt->bindParam(':mois', $this->mois);
            $stmt->bindParam(':annee', $this->annee);

            if ($stmt->execute()) {
                $this->id = $this->db->lastInsertId();
                return true;
            }
            return false;
        } catch (PDOException $e) {
            error_log("Erreur lors de l'insertion de la rémunération : " . $e->getMessage());
            return false;
        }
    }

    public function update() {
        if (empty($this->id)) {
            return false;
        }

        $sql = "UPDATE remuneration SET 
                id_agent = :id_agent, 
                id_affectation = :id_affectation, 
                date_remun = :date_remun, 
                mois = :mois, 
                annee = :annee 
                WHERE id = :id";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id_agent', $this->id_agent);
            $stmt->bindParam(':id_affectation', $this->id_affectation);
            $stmt->bindParam(':date_remun', $this->date_remun);
            $stmt->bindParam(':mois', $this->mois);
            $stmt->bindParam(':annee', $this->annee);
            $stmt->bindParam(':id', $this->id);

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Erreur lors de la mise à jour de la rémunération : " . $e->getMessage());
            return false;
        }
    }

    public function delete() {
        if (empty($this->id)) {
            return false;
        }

        $sql = "DELETE FROM remuneration WHERE id = :id";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $this->id);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Erreur lors de la suppression de la rémunération : " . $e->getMessage());
            return false;
        }
    }

    public static function getById($id) {
        $db = Database::getInstance();
        $sql = "SELECT * FROM remuneration WHERE id = :id";

        try {
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                return new self(
                    $row['id_agent'],
                    $row['id_affectation'],
                    $row['date_remun'],
                    $row['mois'],
                    $row['annee'],
                    $row['id']
                );
            }
            return null;
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération de la rémunération : " . $e->getMessage());
            return null;
        }
    }

    public static function getByAgent($id_agent, $mois = null, $annee = null) {
        $db = Database::getInstance();
        $sql = "SELECT * FROM remuneration WHERE id_agent = :id_agent";
        $params = [':id_agent' => $id_agent];

        if ($mois !== null) {
            $sql .= " AND mois = :mois";
            $params[':mois'] = $mois;
        }

        if ($annee !== null) {
            $sql .= " AND annee = :annee";
            $params[':annee'] = $annee;
        }

        $sql .= " ORDER BY date_remun DESC";

        try {
            $stmt = $db->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();

            $remunerations = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $remunerations[] = new self(
                    $row['id_agent'],
                    $row['id_affectation'],
                    $row['date_remun'],
                    $row['mois'],
                    $row['annee'],
                    $row['id']
                );
            }
            return $remunerations;
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des rémunérations : " . $e->getMessage());
            return [];
        }
    }

    public static function getAll($search = '') {
        $db = Database::getInstance();
        $sql = "SELECT r.*, a.nom_complet, aff.lieu_affectation 
                FROM remuneration r 
                JOIN agent a ON r.id_agent = a.id_agent
                LEFT JOIN affectation aff ON r.id_affectation = aff.id";
        $params = [];

        if ($search !== '') {
            $sql .= " WHERE a.nom_complet LIKE :search OR r.mois LIKE :search OR r.annee LIKE :search OR aff.lieu_affectation LIKE :search";
            $params[':search'] = '%' . $search . '%';
        }

        $sql .= " ORDER BY r.date_remun DESC";

        try {
            $stmt = $db->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();

            $remunerations = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $remunerations[] = new self(
                    $row['id_agent'],
                    $row['id_affectation'],
                    $row['date_remun'],
                    $row['mois'],
                    $row['annee'],
                    $row['id']
                );
            }
            return $remunerations;
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des rémunérations : " . $e->getMessage());
            return [];
        }
    }

    public static function count() {
        $db = Database::getInstance();
        $sql = "SELECT COUNT(*) FROM remuneration";

        try {
            $stmt = $db->query($sql);
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Erreur lors du comptage des rémunérations : " . $e->getMessage());
            return 0;
        }
    }

    public static function getTotalByMonth($mois, $annee) {
        $db = Database::getInstance();
        $sql = "SELECT SUM(aff.montant_remunerer) as total 
                FROM remuneration r
                JOIN affectation aff ON r.id_affectation = aff.id
                WHERE r.mois = :mois AND r.annee = :annee";

        try {
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':mois', $mois);
            $stmt->bindParam(':annee', $annee);
            $stmt->execute();
            $result = $stmt->fetch();
            return $result['total'] ?? 0;
        } catch (PDOException $e) {
            error_log("Erreur lors du calcul du total : " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Récupère le total des rémunérations par affectation
     * @param int $id_affectation ID de l'affectation
     * @return float
     */
    public static function getTotalByAffectation($id_affectation) {
        $db = Database::getInstance();
        $sql = "SELECT COUNT(*) as total FROM remuneration WHERE id_affectation = :id_affectation";

        try {
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':id_affectation', $id_affectation);
            $stmt->execute();
            $result = $stmt->fetch();
            return $result['total'] ?? 0;
        } catch (PDOException $e) {
            error_log("Erreur lors du comptage des rémunérations par affectation : " . $e->getMessage());
            return 0;
        }
    }

    // Relations
    public function getAgent() {
        if ($this->agent === null && $this->id_agent) {
            $this->agent = Agent::getById($this->id_agent);
        }
        return $this->agent;
    }
}
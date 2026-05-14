# Changelog EventTranslator

## v1.0.0 (2026-05-14)

Première version publiée sur le market Jeedom.

### Fonctionnalités

- Ajout d'un équipement source via sélecteur Jeedom standard
- Surveillance de commandes info source (tout plugin compatible)
- Table de correspondance valeur source → action cible :
  - **Valeur** : mise à jour d'une commande info native de l'équipement `_et`
  - **Commande** : exécution directe d'une commande action
  - **Scénario** : lancement immédiat d'un scénario
- **Mode apprentissage** : détection automatique des valeurs en temps réel (countdown 30 s, déduplication)
- Ajout manuel de règles toujours disponible
- Répétition d'événements garantie même si la valeur ne change pas entre deux appuis
- Interface tuiles Jeedom standard : bouton Ajouter (vert) et Configuration (clé)
- Icônes des équipements sources affichées sur les tuiles
- Aucune dépendance de plugin (pas de plugin Virtuel requis)
- Requiert Jeedom 4.2 minimum

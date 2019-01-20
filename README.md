[https://github.com/GroopyMusic/GroopyMusic](https://github.com/GroopyMusic/GroopyMusic)

# Un-Mute asbl

###### *Étapes importantes pour la collaboration au développement de la plateforme*

### Rejoindre le Slack du projet

Pour communiquer facilement et efficacement au sein du projet nous utiliserons Slack. L'espace de communication est disponible [ici](https://un-mute.slack.com/). Une demande d'ajout peut être envoyée à gonzyer@gmail.com

### GitHub

Pour le développement de la plateforme, nous utilisons Github.

Voici donc quelques étapes pour le setup :

  - Avoir un compte [Github](https://github.com/join)
  - demander à [Gonzague](https://github.com/Gounzy) de vous rajouter comme collaborateur.
  - cloner le projet sur votre machine (entrez la commande `git clone https://github.com/Gounzy/GroopyMusic.git` dans votre terminal).

Et voici quelques étapes pour le workflow à adopter :

 ⚠️ Ne jamais entrer la commande `git push origin master` ⚠️

  - Avant de commencer à travailler : `git checkout master && git pull origin master`
  - créer une branche sur laquelle travailler : `git checkout -b NOM_DE_LA_BRANCHE`
  - commit les changements : `git add .`, `git commit -m "mon message personnel et descriptif"`
  - pusher la branche sur github : `git push origin NOM_DE_LA_BRANCHE`


### Créer l'arborescence
Il se peut que vous deviez créer des fichiers dans lesquels seront stockés vos fichiers créés localement (PDF des tickets, commandes ou autres, par exemple). 
Voici les dossiers à créer :
- web/pdf/orders/festivals/
- web/pdf/tickets/
- (à compléter)

### Installer les dépendances avec Composer

Nous utilisons Composer pour gérer les dépendances *third-party* du projet. Il faut avant toutes choses l’installer : <https://getcomposer.org/download/>

Ensuite, il faut disposer d’un fichier `composer.json` qui contient toutes les dépendances du projet. Ce fichier est partagé pour que nous ayons tous les mêmes dépendances ; il se trouve à la racine du projet.

Pour installer les mises-à-jour, exécuter la commande
`php chemin/vers/composer.phar install`
(cette commande doit être lancée depuis le dossier qui contient `composer.json`).

Pour mettre à jour Composer, exécuter
`php chemin/vers/composer.phar self-update`

### Définir les paramètres locaux dans `parameters.yml`

Le fichier `app/config/parameters.yml` n’est pas partagé parce qu’il contient des données « secrètes ».
Lorsque vous rejoignez le projet, Gonzague vous enverra la version de ce fichier qui correspondra à votre environnement local.
S'il vous manque un paramètre après une mise-à-jour, demandez également à Gonzague.

### Mettre en place la base de données

Pour mettre en place la base de données, il faut d’abord exécuter
`php bin/console doctrine:database:create`

Nous utilisons les *migrations* Doctrine pour les mises-à-jour de la base de données.
Dès lors, pour tout changement dans une entité ayant un impact sur la base de données, il s'agit d'exécuter la commande 
`php bin/console doctrine:migrations:diff`
qui créera un fichier de migrations dans le dossier `app/DoctrineMigrations/{annee-courante}/{mois-courant}`. 
Pour exécuter ce fichier de migrations et donc mettre à jour la structure de la base de données en correspondance avec son contenu, il faut utiliser la commande
`php bin/console doctrine:migrations:migrate` et répondre `y` à la question posée. C'est également cette commande qui permet de mettre à jour sa base de données lors d'un pull de la branche master par exemple.

Enfin, il est possible de dire à Doctrine de considérer toutes les migrations comme acquises avec la commande `php bin/console doctrine:migrations:version --add --all`. Cela peut s'avérer utile dans certaines situations - notamment lors de la première installation de la plateforme, où la commande `php bin/console doctrine:schema:update --force` construira la structure de l'ensemble de la base de données, sans passer par les migrations. 

### Les assets

Les fichiers .scss sont compilés ; et, tout comme les fichiers .js, ils sont minifiés lors de la génération des assets.

Il vous faut donc : 
- installer `uglifycss` et `uglifyjs`
- renseigner dans le fichier `parameters.yml` le lien d'accès sur votre machine vers ces deux minifieurs
- lors de l'installation de la plateforme, exécuter la commande `php bin/console assets:install` afin d'installer les assets des vendors. Cette commande est normalement exécutée automatiquement par Composer lors de chaque update
- à chaque modification des assets, et lors de l'installation de la plateforme sur votre machine, utiliser la commande `php bin/console assetic:dump --env=dev` pour générer les assets compilés (et minifiés le cas échéant)

# SAE3.01 Développement d'application

Ce tutoriel de déploiement est destiné au déploiement de l'application dans un enviornnement permettant de tester les fonctionnalités et non le déploiement de l'application en production.

Il est destiné au super-administrateur de l'application ayant accès à une base de donnée.

## Equipe

- DESSUP Lyam
- GABARRA Illan
- GODINAT Caetano
- MOREIRA Aymeric
- NAIB Oualid
- REHMAN Asad-Ur

## Environnement

Ce guide déploiement a été testé sur un environnement de travail à l'IUT de Bordeaux dans une session personnelle dans système d'exploitation Linux Ubuntu

## Installation de l'application

L'application est disponible sur le gitlab de l'IUT de Bordeaux.

Vous pouvez cloner le projet avec la commande suivante :

```git
git clone git@gitlab-ce.iut.u-bordeaux.fr:igabarra/s3.01-devapp-equipe12.git
```

Déplacez vous dans le dossier /s3.01-devapp-equipe12/symfony pour la suite du guide.

```console
cd s3.01-devapp-equipe12/symfony
```

## Base de données

### Accéder aux identifiants de la base de données de l'IUT

- se connecter au CAS de l'université
- pour accéder aux identifiants https://gregwar.com/bdd.u-bordeaux.fr/
- aller dans https://apps.iut.u-bordeaux.fr/phpmyadmin/ pour accéder à la base de données
- sélectionner la base de données info-titania
- metre les identifiants
- dans https://gregwar.com/s3web/environment.html#title.1 télécharger la base de données compréssée shows.sql.zip
- dans phpmyadmin sélectionner une base de données
- clicker sur importé et sélectionner le fichier shows.sql.zip
- clicker sur exécuter

### Paramètrage de la base de données dans symfony

Dans le dossier `/s3.01-devapp-equipe12/symfony`

vous devez créer un fichier `.env.local` et y mettre les informations suivantes :

```config
DATABASE_URL="mysql://<user>:<motDePasse>@info-titania/<baseDeDonnées>"
```

changez les valeurs entre <> par les valeurs de votre base de données.


## Installation de symfony

Si symfony est déjà installer vous vouvez passer cette étape.

### Dans la console tapez les commandes suivantes

```console
wget https://get.symfony.com/cli/installer -O - | bash
export PATH="$HOME/.symfony5/bin:$PATH"
```

### Vérification de l'installation

```console
symfony check:requirements
```

## Installation des dépendances

```console
symfony composer install
```

## Lancement du serveur de test de symfony


Ce serveur ne doit pas être utilisé comme un serveur de production.

### dans le dossier /s3.01-devapp-equipe12/symfony

```console
symfony serve
```
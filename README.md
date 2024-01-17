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

Ce guide déploiement a été testé sur un environnement de travail à l'IUT de Bordeaux dans une session personnelle avec le système d'exploitation Linux Ubuntu

Mais également dans un environnement vierge également avec le système d'exploitation Linux Ubuntu

## Installation du code de l'application

L'application est disponible sur le gitlab de l'IUT de Bordeaux.

Vous pouvez cloner le projet avec la commande suivante :

```git
git clone git@gitlab-ce.iut.u-bordeaux.fr:igabarra/s3.01-devapp-equipe12.git
```

Déplacez vous dans le dossier /s3.01-devapp-equipe12/symfony pour la suite du guide.

```console
cd s3.01-devapp-equipe12/symfony
```

## Installation des dépendances du projet

## Installation de php

Pour installer PHP, vous pouvez utiliser la commande suivante dans votre terminal :

```console
sudo apt-get install php
```

## Installation de MySQL

L'installation de MySQL est nécessaire pour la base de données seulement si vous n'avez pas accès à une base de données de l'IUT.

Cette installation est faite pour une utilisation d'une base de données en local.

Pour installer MySQL, vous pouvez utiliser la commande suivante dans votre terminal :

```console
sudo apt-get install mysql-server
```

### Création d'un utilisateur MySQL

Connectez vous à MySQL en tant que root

```console
sudo mysql -u root -p
```

Creation d'un tout nouveau utilisateur

```sql
CREATE USER '<nouveau_utilisateur>'@'localhost' IDENTIFIED BY '<mot_de_passe>';
```

Création d'une base de données

```sql
CREATE DATABASE <nom_de_la_base_de_données>;
```

Attribution des droits à l'utilisateur sur la base de données

```sql
GRANT ALL PRIVILEGES ON <nom_de_la_base_de_données>.* TO '<nouveau_utilisateur>'@'localhost';
```

### Verification de l'installation

```console
sudo systemctl status mysql
```

Si le service est actif, vous pouvez passer à l'étape suivante.

## Installation de symfony

Si symfony est déjà installé vous pouvez passer cette étape.

### Dans la console tapez les commandes suivantes

```console
wget https://get.symfony.com/cli/installer -O - | bash
```

Puis ajoutez le chemin de symfony dans votre PATH en ajoutant la ligne suivante dans votre fichier `~/.bashrc`

```console
export PATH="$HOME/.symfony5/bin:$PATH"
```

### Vérification de l'installation de symfony

```console
symfony check:requirements
```

## Installation des dépendances symfony

Dans le dossier `/s3.01-devapp-equipe12/symfony`

```console
symfony composer install
```

## Base de données

Il existe deux moyens pour configurer la base de donnée qui sera utilisé pour votre environnement de test.

- Soit dans l'environnement de l'IUT avec la base fournit par le département informatique

- Soit dans un environnement vierge, dit "From Scratch"

### Base de données de l'IUT

- se connecter au CAS de l'université
- pour accéder aux identifiants https://gregwar.com/bdd.u-bordeaux.fr/
- aller dans https://apps.iut.u-bordeaux.fr/phpmyadmin/ pour accéder à la base de données
- sélectionner la base de données info-titania
- metre les identifiants
- dans https://gregwar.com/s3web/environment.html#title.1 télécharger la base de données compréssée shows.sql.zip
- dans phpmyadmin sélectionner une base de données
- clicker sur importé et sélectionner le fichier shows.sql.zip
- clicker sur exécuter

### Base de donnée installé "From Scratch"

Vous avez installé MySQL et créé une base de donnée dans la partie "Installation de MySQL" de ce guide.

### Importation de la base de données

Installer le fichier [show.sql](https://gregwar.com/s3web/files/shows.sql)

Puis importer le fichier dans votre base de données.

```console
mysql -u <user> -p <database> < shows.sql
```

### Paramètrage de la base de données dans symfony

Dans le dossier `/s3.01-devapp-equipe12/symfony`

vous devez créer un fichier `.env.local` et y mettre les informations suivantes :

```config
DATABASE_URL="mysql://<user>:<motDePasse>@localhost/<baseDeDonnées>"
```

changez les valeurs entre <> par les valeurs de votre installation.

## Lancement du serveur de test de symfony

Ce serveur ne doit pas être utilisé comme un serveur de production.

### dans le dossier /s3.01-devapp-equipe12/symfony

```console
symfony serve
```

## Accès à l'application

L'application est accessible à l'adresse suivante :

```url
http://localhost:8000
```

## Commandes super-administrateur

Toutes les commandes suivantes sont à effectuer dans le dossier `/symfony`

### Création d'un super-administrateur

met un utilisateur donné en super-administrateur.

```bash
symfony console app:new-super-admin <email>
```

### Création d'utilisateurs aléatoires

crée un nombre parametrable d'utilisateurs aléatoires.
Leur mot de passe est son mail, et leur nombre admin dans la base de données est -1.

```bash
symfony console app:create-users <nombre d'utilisateurs>
```
par défaut le nombre d'utilisateurs est 10 si vous ne mettez pas de nombre d'utilisateurs.

### Création de critiques aléatoires

crée des critiques aléatoires pour chaque utilisateur,
avec un nombre de critiques par utilisateur compris dans une plage parametrable. Et des notes aléatoires pour chaque critique et série.

```bash
symfony console app:create-rating <nombre minimum de critiques par utilisateur> <nombre maximum de critiques par utilisateur>
```
par défaut le nombre minimum de critiques par utilisateur est 10 et le nombre maximum de critiques par utilisateur est 150 si vous ne mettez pas de nombre minimum et maximum de critiques par utilisateur.



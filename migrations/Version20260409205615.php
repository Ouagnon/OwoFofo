<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260409205615 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE classement_perdant (id INT AUTO_INCREMENT NOT NULL, id_manche INT NOT NULL, id_element INT NOT NULL, id_theme INT DEFAULT NULL, rang SMALLINT NOT NULL, INDEX IDX_966C69FE8659D706 (id_manche), INDEX IDX_966C69FE9FDDF749 (id_element), INDEX IDX_966C69FE79F0A638 (id_theme), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE decision_impair (id INT AUTO_INCREMENT NOT NULL, id_manche INT NOT NULL, id_element_impair INT NOT NULL, id_element_remplacant INT DEFAULT NULL, action_impair VARCHAR(16) NOT NULL, date_resolution DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_AE7E70DB8659D706 (id_manche), INDEX IDX_AE7E70DB63F4392A (id_element_impair), INDEX IDX_AE7E70DB98864792 (id_element_remplacant), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE duel (id INT AUTO_INCREMENT NOT NULL, id_manche INT NOT NULL, id_element_a INT NOT NULL, id_element_b INT NOT NULL, id_vainqueur INT DEFAULT NULL, ordre SMALLINT NOT NULL, etat_duel VARCHAR(16) NOT NULL, type_confrontation VARCHAR(16) NOT NULL, INDEX IDX_9BB4A7628659D706 (id_manche), INDEX IDX_9BB4A762442D0E84 (id_element_a), INDEX IDX_9BB4A762DD245F3E (id_element_b), INDEX IDX_9BB4A762EF18D856 (id_vainqueur), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE element (id INT AUTO_INCREMENT NOT NULL, id_tournoi INT NOT NULL, id_theme INT DEFAULT NULL, titre VARCHAR(255) NOT NULL, url_media VARCHAR(2048) NOT NULL, type_media VARCHAR(16) NOT NULL, seed SMALLINT DEFAULT NULL, actif TINYINT(1) DEFAULT 1 NOT NULL, date_creation DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_41405E39C63270AF (id_tournoi), INDEX IDX_41405E3979F0A638 (id_theme), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE joueur (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(80) NOT NULL, mot_de_passe_hash VARCHAR(255) NOT NULL, date_creation DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_FD71A9C56C6E55B5 (nom), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE manche (id INT AUTO_INCREMENT NOT NULL, id_partie INT NOT NULL, numero SMALLINT NOT NULL, type_manche VARCHAR(8) NOT NULL, etat_manche VARCHAR(32) NOT NULL, mode_appariement VARCHAR(32) NOT NULL, date_creation DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', date_fin DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_A06E62EB23ACAAD0 (id_partie), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE partie (id INT AUTO_INCREMENT NOT NULL, id_joueur INT NOT NULL, id_tournoi INT NOT NULL, id_vainqueur_final INT DEFAULT NULL, etat_partie VARCHAR(32) NOT NULL, date_creation DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', date_modification DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', date_debut DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', date_fin DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_59B1F3DDB461C28 (id_joueur), INDEX IDX_59B1F3DC63270AF (id_tournoi), INDEX IDX_59B1F3D32AC87C5 (id_vainqueur_final), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE repechage (id INT AUTO_INCREMENT NOT NULL, id_manche INT NOT NULL, id_theme INT DEFAULT NULL, id_perdant INT NOT NULL, id_vainqueur_cible INT NOT NULL, id_vainqueur_final INT DEFAULT NULL, ordre SMALLINT NOT NULL, etat_duel VARCHAR(16) NOT NULL, INDEX IDX_6A7704318659D706 (id_manche), INDEX IDX_6A77043179F0A638 (id_theme), INDEX IDX_6A77043125F5B92C (id_perdant), INDEX IDX_6A7704313F243738 (id_vainqueur_cible), INDEX IDX_6A77043132AC87C5 (id_vainqueur_final), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE theme (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(120) NOT NULL, slug VARCHAR(120) NOT NULL, UNIQUE INDEX UNIQ_9775E7086C6E55B5 (nom), UNIQUE INDEX UNIQ_9775E708989D9B62 (slug), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tournoi (id INT AUTO_INCREMENT NOT NULL, id_createur INT DEFAULT NULL, id_theme_a INT DEFAULT NULL, id_theme_b INT DEFAULT NULL, nom VARCHAR(120) NOT NULL, description LONGTEXT DEFAULT NULL, mode_tournoi VARCHAR(32) NOT NULL, forcer_confrontation_themes TINYINT(1) DEFAULT 1 NOT NULL, gerer_impair TINYINT(1) DEFAULT 1 NOT NULL, activer_repechage TINYINT(1) DEFAULT 1 NOT NULL, activer_classement_perdants TINYINT(1) DEFAULT 1 NOT NULL, taille_tableau_cible SMALLINT DEFAULT NULL, date_creation DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', date_modification DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_18AFD9DFAA033611 (id_createur), INDEX IDX_18AFD9DF6E65C17C (id_theme_a), INDEX IDX_18AFD9DFF76C90C6 (id_theme_b), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', available_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE classement_perdant ADD CONSTRAINT FK_966C69FE8659D706 FOREIGN KEY (id_manche) REFERENCES manche (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE classement_perdant ADD CONSTRAINT FK_966C69FE9FDDF749 FOREIGN KEY (id_element) REFERENCES element (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE classement_perdant ADD CONSTRAINT FK_966C69FE79F0A638 FOREIGN KEY (id_theme) REFERENCES theme (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE decision_impair ADD CONSTRAINT FK_AE7E70DB8659D706 FOREIGN KEY (id_manche) REFERENCES manche (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE decision_impair ADD CONSTRAINT FK_AE7E70DB63F4392A FOREIGN KEY (id_element_impair) REFERENCES element (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE decision_impair ADD CONSTRAINT FK_AE7E70DB98864792 FOREIGN KEY (id_element_remplacant) REFERENCES element (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE duel ADD CONSTRAINT FK_9BB4A7628659D706 FOREIGN KEY (id_manche) REFERENCES manche (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE duel ADD CONSTRAINT FK_9BB4A762442D0E84 FOREIGN KEY (id_element_a) REFERENCES element (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE duel ADD CONSTRAINT FK_9BB4A762DD245F3E FOREIGN KEY (id_element_b) REFERENCES element (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE duel ADD CONSTRAINT FK_9BB4A762EF18D856 FOREIGN KEY (id_vainqueur) REFERENCES element (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE element ADD CONSTRAINT FK_41405E39C63270AF FOREIGN KEY (id_tournoi) REFERENCES tournoi (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE element ADD CONSTRAINT FK_41405E3979F0A638 FOREIGN KEY (id_theme) REFERENCES theme (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE manche ADD CONSTRAINT FK_A06E62EB23ACAAD0 FOREIGN KEY (id_partie) REFERENCES partie (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE partie ADD CONSTRAINT FK_59B1F3DDB461C28 FOREIGN KEY (id_joueur) REFERENCES joueur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE partie ADD CONSTRAINT FK_59B1F3DC63270AF FOREIGN KEY (id_tournoi) REFERENCES tournoi (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE partie ADD CONSTRAINT FK_59B1F3D32AC87C5 FOREIGN KEY (id_vainqueur_final) REFERENCES element (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE repechage ADD CONSTRAINT FK_6A7704318659D706 FOREIGN KEY (id_manche) REFERENCES manche (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE repechage ADD CONSTRAINT FK_6A77043179F0A638 FOREIGN KEY (id_theme) REFERENCES theme (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE repechage ADD CONSTRAINT FK_6A77043125F5B92C FOREIGN KEY (id_perdant) REFERENCES element (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE repechage ADD CONSTRAINT FK_6A7704313F243738 FOREIGN KEY (id_vainqueur_cible) REFERENCES element (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE repechage ADD CONSTRAINT FK_6A77043132AC87C5 FOREIGN KEY (id_vainqueur_final) REFERENCES element (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE tournoi ADD CONSTRAINT FK_18AFD9DFAA033611 FOREIGN KEY (id_createur) REFERENCES joueur (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE tournoi ADD CONSTRAINT FK_18AFD9DF6E65C17C FOREIGN KEY (id_theme_a) REFERENCES theme (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE tournoi ADD CONSTRAINT FK_18AFD9DFF76C90C6 FOREIGN KEY (id_theme_b) REFERENCES theme (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE classement_perdant DROP FOREIGN KEY FK_966C69FE8659D706');
        $this->addSql('ALTER TABLE classement_perdant DROP FOREIGN KEY FK_966C69FE9FDDF749');
        $this->addSql('ALTER TABLE classement_perdant DROP FOREIGN KEY FK_966C69FE79F0A638');
        $this->addSql('ALTER TABLE decision_impair DROP FOREIGN KEY FK_AE7E70DB8659D706');
        $this->addSql('ALTER TABLE decision_impair DROP FOREIGN KEY FK_AE7E70DB63F4392A');
        $this->addSql('ALTER TABLE decision_impair DROP FOREIGN KEY FK_AE7E70DB98864792');
        $this->addSql('ALTER TABLE duel DROP FOREIGN KEY FK_9BB4A7628659D706');
        $this->addSql('ALTER TABLE duel DROP FOREIGN KEY FK_9BB4A762442D0E84');
        $this->addSql('ALTER TABLE duel DROP FOREIGN KEY FK_9BB4A762DD245F3E');
        $this->addSql('ALTER TABLE duel DROP FOREIGN KEY FK_9BB4A762EF18D856');
        $this->addSql('ALTER TABLE element DROP FOREIGN KEY FK_41405E39C63270AF');
        $this->addSql('ALTER TABLE element DROP FOREIGN KEY FK_41405E3979F0A638');
        $this->addSql('ALTER TABLE manche DROP FOREIGN KEY FK_A06E62EB23ACAAD0');
        $this->addSql('ALTER TABLE partie DROP FOREIGN KEY FK_59B1F3DDB461C28');
        $this->addSql('ALTER TABLE partie DROP FOREIGN KEY FK_59B1F3DC63270AF');
        $this->addSql('ALTER TABLE partie DROP FOREIGN KEY FK_59B1F3D32AC87C5');
        $this->addSql('ALTER TABLE repechage DROP FOREIGN KEY FK_6A7704318659D706');
        $this->addSql('ALTER TABLE repechage DROP FOREIGN KEY FK_6A77043179F0A638');
        $this->addSql('ALTER TABLE repechage DROP FOREIGN KEY FK_6A77043125F5B92C');
        $this->addSql('ALTER TABLE repechage DROP FOREIGN KEY FK_6A7704313F243738');
        $this->addSql('ALTER TABLE repechage DROP FOREIGN KEY FK_6A77043132AC87C5');
        $this->addSql('ALTER TABLE tournoi DROP FOREIGN KEY FK_18AFD9DFAA033611');
        $this->addSql('ALTER TABLE tournoi DROP FOREIGN KEY FK_18AFD9DF6E65C17C');
        $this->addSql('ALTER TABLE tournoi DROP FOREIGN KEY FK_18AFD9DFF76C90C6');
        $this->addSql('DROP TABLE classement_perdant');
        $this->addSql('DROP TABLE decision_impair');
        $this->addSql('DROP TABLE duel');
        $this->addSql('DROP TABLE element');
        $this->addSql('DROP TABLE joueur');
        $this->addSql('DROP TABLE manche');
        $this->addSql('DROP TABLE partie');
        $this->addSql('DROP TABLE repechage');
        $this->addSql('DROP TABLE theme');
        $this->addSql('DROP TABLE tournoi');
        $this->addSql('DROP TABLE messenger_messages');
    }
}

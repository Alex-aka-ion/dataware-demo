<?php

declare(strict_types=1);

namespace DoctrineMigrations\OrderService;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250131180214 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE orders (id UUID NOT NULL, delivery_address TEXT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('COMMENT ON COLUMN orders.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN orders.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE order_item (id UUID NOT NULL, order_id UUID NOT NULL, product_id UUID NOT NULL, quantity INT NOT NULL, price INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_52EA1F098D9F6D38 ON order_item (order_id)');
        $this->addSql('COMMENT ON COLUMN order_item.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN order_item.order_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN order_item.product_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE order_item ADD CONSTRAINT FK_52EA1F098D9F6D38 FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE order_item DROP CONSTRAINT FK_52EA1F098D9F6D38');
        $this->addSql('DROP TABLE orders');
        $this->addSql('DROP TABLE order_item');
    }
}

#### add one by one - some dbs have some of the collums already

alter table tbl_product add column sort int(5)


alter table tbl_product add column has_coupon tinyint(1)

alter table tbl_product add column has_variation tinyint(1)

alter table tbl_product add column pos tinyint(1)

alter table tbl_product add column idPrinter tinyint(1)

ALTER TABLE `tbl_product` ADD `product_type` VARCHAR(10) NULL DEFAULT NULL ;



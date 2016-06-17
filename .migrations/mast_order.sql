#### add one by one - some dbs have some of the collums already

alter table mast_order add column from_mobile tinyint(1)
alter table mast_order add column status varchar(50)

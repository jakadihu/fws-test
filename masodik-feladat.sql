set @product_package_id = 9;
set @datetime = '2024-02-11 10:22:16';

select sum(total) as total from (
    select	ppc.product_id,
            ppc.quantity,
            ph.updated_at,
            (ph.price * ppc.quantity) as total
        from product_package_contents as ppc
        inner join price_history as ph
            on ph.product_id = ppc.product_id
            and ph.updated_at = (select MAX(pht.updated_at)
                from price_history as pht
                where pht.updated_at < @datetime
                and pht.product_id = ppc.product_id)
        where ppc.product_package_id = @product_package_id
) as phs;
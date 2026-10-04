/**
 * Kit Shop's public frontend entry.
 */
import { Package, Shirt } from '@lucide/vue';
import { defineModule } from '@/lib/modules';
import kitShop from '@/routes/kit-shop';

export default defineModule({
    key: 'kit-shop',
    navigation: [
        { title: 'Kit shop', href: kitShop.index(), icon: Shirt },
        {
            title: 'Kit products',
            href: kitShop.products.index(),
            icon: Package,
            roles: ['admin'],
        },
    ],
});

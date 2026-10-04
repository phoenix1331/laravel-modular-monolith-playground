export type StockLine = { size: string; quantity: number };

export type Product = {
    id: number;
    name: string;
    price_pence: number;
    stock: StockLine[];
};

const money = new Intl.NumberFormat('en-GB', {
    style: 'currency',
    currency: 'GBP',
});

export const pounds = (pence: number): string => money.format(pence / 100);

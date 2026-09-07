# Home Menu Glimpse media contract

Each category uses this exact folder shape:

```text
{category-key}/
  background/
    one-image.jpg|jpeg|png|webp
  foreground/
    01.png
    02.png
    03.png
```

Use exactly one eligible background and one to three transparent PNG foreground food cutouts. The background stays static while the foreground PNGs animate, then the section ends on its CTA frame. Invalid or incomplete category folders are ignored. If no valid category exists, the Home renders its intentional menu-imagery placeholder with an Order Now link.

Use keys such as `burgers`, `sandwiches`, `chicken`, `fries`, `salads`, `drinks`, `ice-cream`, and `family-packs`; valid extra keys follow natural folder order.

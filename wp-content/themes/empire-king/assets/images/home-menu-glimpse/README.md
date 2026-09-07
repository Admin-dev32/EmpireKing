# Home Menu Glimpse media contract

Codex does not create this media. Jorge supplies every image.

```text
burgers/       background/  foreground/
sandwiches/    background/  foreground/
chicken/       background/  foreground/
fries/         background/  foreground/
salads/        background/  foreground/
drinks/        background/  foreground/
ice-cream/     background/  foreground/
family-packs/  background/  foreground/
```

For each category, add exactly one background (`.webp`, `.jpg`, `.jpeg`, or `.png`) and one to three transparent foreground `.png` cutouts. Filenames are arbitrary; natural filename order controls the foreground sequence. For example, `01-burger.png`, `02-double.png`, and `03-bacon.png` play in that order.

Incomplete categories are ignored. Adding valid files automatically enables a category after refresh; removing required files disables it. The `.gitkeep` files preserve empty folders and are ignored by discovery.

# Changelog Entry for Migration 1738955309

**Summary:**
Added additional transitions for the "paid" status in the Order Transaction State Machine to resolve an inconsistency. According to the documentation, the action "paid" should be used to mark an order as fully paid. However, only the legacy action "pay" was available. This migration adds the following transitions without removing the legacy "pay" transition:

- Transition from `reminded` to `paid_partially` using action "paid_partially".
- Transition from `reminded` to `paid` using action "paid".
- Transition from `paid_partially` to `paid` using action "paid".

**Reason:**
This change ensures that the documented action "paid" is available for transitioning orders to a fully paid state, improving consistency across the platform while maintaining backward compatibility.

**Impact:**
The migration does not remove any existing transitions; it only adds new ones if they are not already present. All environments will receive this update automatically during the migration process.


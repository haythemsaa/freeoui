import { test, expect } from '@playwright/test'

test.describe('Advantages Management', () => {
  test.beforeEach(async ({ page }) => {
    // Login before each test
    await page.goto('/login')
    await page.getByLabel(/email/i).fill('merchant@freeoui.tn')
    await page.getByLabel(/mot de passe/i).fill('password123')
    await page.getByRole('button', { name: /connexion/i }).click()
    await page.waitForURL(/\/dashboard/)

    // Navigate to advantages page
    await page.getByRole('link', { name: /avantages/i }).click()
  })

  test('should display advantages list', async ({ page }) => {
    await expect(page.getByRole('heading', { name: /gestion des avantages/i })).toBeVisible()
    await expect(page.getByRole('button', { name: /nouvel avantage/i })).toBeVisible()
  })

  test('should open create advantage modal', async ({ page }) => {
    await page.getByRole('button', { name: /nouvel avantage/i }).click()

    await expect(page.getByRole('heading', { name: /nouvel avantage/i })).toBeVisible()
    await expect(page.getByLabel(/titre/i)).toBeVisible()
    await expect(page.getByLabel(/description/i)).toBeVisible()
  })

  test('should create a new advantage', async ({ page }) => {
    await page.getByRole('button', { name: /nouvel avantage/i }).click()

    // Fill form
    await page.getByLabel(/titre/i).fill('Test Advantage E2E')
    await page.getByLabel(/description/i).fill('Description for E2E test')
    await page.getByLabel(/type/i).selectOption('percentage')
    await page.getByLabel(/pourcentage/i).fill('20')

    // Select days
    await page.getByRole('checkbox', { name: /lundi/i }).check()
    await page.getByRole('checkbox', { name: /mardi/i }).check()

    // Submit
    await page.getByRole('button', { name: /créer/i }).click()

    // Should show success message
    await expect(page.getByText(/avantage créé avec succès/i)).toBeVisible()

    // Should see the new advantage in the list
    await expect(page.getByText('Test Advantage E2E')).toBeVisible()
  })

  test('should edit an advantage', async ({ page }) => {
    // Click edit on first advantage
    await page.locator('[data-testid="edit-advantage"]').first().click()

    await expect(page.getByRole('heading', { name: /modifier l'avantage/i })).toBeVisible()

    // Update title
    const titleInput = page.getByLabel(/titre/i)
    await titleInput.clear()
    await titleInput.fill('Updated Advantage Title')

    // Submit
    await page.getByRole('button', { name: /modifier/i }).click()

    // Should show success message
    await expect(page.getByText(/avantage mis à jour avec succès/i)).toBeVisible()
  })

  test('should delete an advantage', async ({ page }) => {
    // Click delete on first advantage
    await page.locator('[data-testid="delete-advantage"]').first().click()

    // Confirm deletion
    await page.getByRole('button', { name: /confirmer/i }).click()

    // Should show success message
    await expect(page.getByText(/avantage supprimé avec succès/i)).toBeVisible()
  })

  test('should filter advantages by status', async ({ page }) => {
    await page.getByLabel(/statut/i).selectOption('active')

    // Should only show active advantages
    await expect(page.getByText(/actif/i)).toBeVisible()
  })

  test('should search advantages', async ({ page }) => {
    const searchInput = page.getByPlaceholder(/rechercher/i)
    await searchInput.fill('Café')

    // Should filter results
    await expect(page.getByText(/café/i)).toBeVisible()
  })
})

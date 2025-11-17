import { test, expect } from '@playwright/test'

test.describe('Analytics Dashboard', () => {
  test.beforeEach(async ({ page }) => {
    // Login
    await page.goto('/login')
    await page.getByLabel(/email/i).fill('merchant@freeoui.tn')
    await page.getByLabel(/mot de passe/i).fill('password123')
    await page.getByRole('button', { name: /connexion/i }).click()
    await page.waitForURL(/\/dashboard/)
  })

  test('should display dashboard metrics', async ({ page }) => {
    await expect(page.getByText(/scans aujourd'hui/i)).toBeVisible()
    await expect(page.getByText(/utilisateurs actifs/i)).toBeVisible()
    await expect(page.getByText(/économies générées/i)).toBeVisible()
    await expect(page.getByText(/taux de conversion/i)).toBeVisible()
  })

  test('should display charts', async ({ page }) => {
    await expect(page.getByText(/scans sur 7 jours/i)).toBeVisible()
    await expect(page.getByText(/top 5 avantages/i)).toBeVisible()
    await expect(page.getByText(/distribution horaire/i)).toBeVisible()
  })

  test('should navigate to analytics page', async ({ page }) => {
    await page.getByRole('link', { name: /analytiques/i }).click()

    await expect(page).toHaveURL(/\/analytics/)
    await expect(page.getByRole('heading', { name: /analytiques/i })).toBeVisible()
  })

  test('should generate custom report', async ({ page }) => {
    await page.getByRole('link', { name: /analytiques/i }).click()

    await page.getByLabel(/date de début/i).fill('2024-01-01')
    await page.getByLabel(/date de fin/i).fill('2024-01-31')
    await page.getByRole('button', { name: /générer un rapport/i }).click()

    await expect(page.getByText(/rapport généré/i)).toBeVisible()
  })

  test('should export data', async ({ page }) => {
    await page.getByRole('link', { name: /analytiques/i }).click()

    const downloadPromise = page.waitForEvent('download')
    await page.getByRole('button', { name: /exporter/i }).click()
    const download = await downloadPromise

    expect(download.suggestedFilename()).toMatch(/\.csv$/)
  })
})

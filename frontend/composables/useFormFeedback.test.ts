import { describe, expect, it } from 'vitest';
import { extractApiErrorMessage, extractApiFieldErrors, useFormFeedback } from './useFormFeedback';

describe('useFormFeedback', () => {
    it('extracts backend field errors', () => {
        expect(extractApiFieldErrors({
            data: {
                errors: {
                    email: 'Bitte E-Mail angeben.',
                    'address.city': 'Bitte Ort angeben.',
                },
            },
        })).toEqual({
            email: 'Bitte E-Mail angeben.',
            'address.city': 'Bitte Ort angeben.',
        });
    });

    it('prefers a field summary when validation errors exist', () => {
        const message = extractApiErrorMessage({
            data: {
                errors: {
                    email: 'Bitte E-Mail angeben.',
                },
            },
        }, 'Fallback');

        expect(message).toBe('Bitte prüfe die markierten Felder.');
    });

    it('returns singular api errors when no field map exists', () => {
        const message = extractApiErrorMessage({
            data: {
                error: 'Nicht genug Credits.',
            },
        }, 'Fallback');

        expect(message).toBe('Nicht genug Credits.');
    });

    it('tracks and clears form errors locally', () => {
        const feedback = useFormFeedback();

        feedback.setFieldError('name', 'Bitte Namen angeben.');
        feedback.setFormError('Bitte prüfen.');

        expect(feedback.errorFor('name')).toBe('Bitte Namen angeben.');
        expect(feedback.formError.value).toBe('Bitte prüfen.');

        feedback.clearFieldError('name');
        feedback.clearFormErrors();

        expect(feedback.errorFor('name')).toBeUndefined();
        expect(feedback.formError.value).toBe('');
    });
});

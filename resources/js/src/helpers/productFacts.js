// A product the user saves from the voice page joins the shared database when its
// nutrition facts are the ones it came with, from the database or the model, and
// becomes the user's own product when the user changed them. The weight is how
// much was eaten: changing it changes nothing about the product.

const FACTS = ["calories", "protein", "fats", "carbs"];

/**
 * @param {Object} product a product of the voice page
 * @returns {Object} its nutrition facts per 100 g
 */
export const factsOf = (product) =>
    Object.fromEntries(FACTS.map((field) => [field, product[field]]));

/**
 * @param {Object} product a product of the voice page
 * @param {Object|undefined} original the facts it came with, from factsOf()
 * @returns {boolean} whether the user changed the facts
 */
export const factsChanged = (product, original) =>
    original !== undefined &&
    FACTS.some((field) => Number(product[field]) !== Number(original[field]));

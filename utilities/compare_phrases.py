import spacy
import sys

nlp = spacy.load("en_core_web_lg")

def compare_phrases(text1, text2):
    doc1 = nlp(text1)
    doc2 = nlp(text2)
    similarity = doc1.similarity(doc2)
    return similarity

if __name__ == "__main__":
    if len(sys.argv) < 3:
        print("Usage: python compare_phrases.py 'phrase one' 'phrase two'")
        sys.exit(1)

    phrase1 = sys.argv[1]
    phrase2 = sys.argv[2]
    sim = compare_phrases(phrase1, phrase2)
    print(f"Similarity between:\n  \"{phrase1}\"\n  \"{phrase2}\"\n→ {sim:.3f}")

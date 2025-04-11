import spacy
import sys

# Load the spaCy model
nlp = spacy.load("en_core_web_md")

# Process the input text (from command-line arguments)
text = " ".join(sys.argv[1:])
doc = nlp(text)

# Extract and display the named entities, noun phrases, and part-of-speech tags
print("Named Entities, Phrases, and Concepts:")
for ent in doc.ents:
    print(f"{ent.text} ({ent.label_})")

print("\nNoun Phrases:")
for np in doc.noun_chunks:
    print(np.text)

print("\nPOS Tags:")
for token in doc:
    print(f"{token.text} - {token.pos_}")
<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: "series", uniqueConstraints: [
    new ORM\UniqueConstraint(name: "UNIQ_3A10012D85489131", columns: ["imdb"])
], indexes: [
    new ORM\Index(name: "I_SERIES_TITLE", columns: ["title"]),
    new ORM\Index(name: "I_SERIES_PLOT", columns: ["plot"]),
    new ORM\Index(name: "I_SERIES_GENRE_ID", columns: ["genre_id"]),
    new ORM\Index(name: "I_SERIES_YEAR_START", columns: ["year_start"]),
    new ORM\Index(name: "I_SERIES_YEAR_END", columns: ["year_end"])
])]
#[ORM\Entity(repositoryClass: "App\Repository\SeriesRepository")]
class Series
{
    #[ORM\Column(name: "id", type: "integer", nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "IDENTITY")]
    private $id;

    #[ORM\Column(name: "title", type: "string", length: 128, nullable: false)]
    private $title;

    #[ORM\Column(name: "plot", type: "text", length: 0, nullable: true)]
    private $plot;

    #[ORM\Column(name: "imdb", type: "string", length: 128, nullable: false)]
    private $imdb;

    #[ORM\Column(name: "poster", type: "blob", length: 0, nullable: true)]
    private $poster;

    #[ORM\Column(name: "director", type: "string", length: 128, nullable: true)]
    private $director;

    #[ORM\Column(name: "youtube_trailer", type: "string", length: 128, nullable: true)]
    private $youtubeTrailer;

    #[ORM\Column(name: "awards", type: "text", length: 0, nullable: true)]
    private $awards;

    #[ORM\Column(name: "year_start", type: "integer", nullable: true)]
    private $yearStart;

    #[ORM\Column(name: "year_end", type: "integer", nullable: true)]
    private $yearEnd;

    #[ORM\ManyToMany(targetEntity: "User", mappedBy: "series")]
    private $user = array();

    #[ORM\ManyToMany(targetEntity: "Genre", mappedBy: "series", fetch:"EAGER")]
    #[ORM\JoinTable(name: "genre_series")]
    private $genre = array();

    #[ORM\ManyToMany(targetEntity: "Actor", mappedBy: "series")]
    private $actor = array();

    #[ORM\ManyToMany(targetEntity: "Country", mappedBy: "series")]
    private $country = array();

    #[ORM\OneToMany(targetEntity: "Season", mappedBy: "series")]
    #[ORM\OrderBy(["number" => "ASC"])]
    private $seasons;

    #[ORM\OneToMany(mappedBy: "series", targetEntity: "ExternalRating")]
    private $externalRatings;

    #[ORM\OneToMany(mappedBy: "series", targetEntity: "Rating", fetch:"EAGER")]
    private $ratings;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->user = new \Doctrine\Common\Collections\ArrayCollection();
        $this->actor = new \Doctrine\Common\Collections\ArrayCollection();
        $this->country = new \Doctrine\Common\Collections\ArrayCollection();
        $this->seasons = new ArrayCollection();
        $this->genre = new ArrayCollection();
        $this->externalRatings = new ArrayCollection();
        $this->ratings = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getPlot(): ?string
    {
        return $this->plot;
    }

    public function setPlot(?string $plot): self
    {
        $this->plot = $plot;

        return $this;
    }

    public function getImdb(): ?string
    {
        return $this->imdb;
    }

    public function setImdb(string $imdb): self
    {
        $this->imdb = $imdb;

        return $this;
    }

    public function getDirector(): ?string
    {
        return $this->director;
    }

    public function setDirector(?string $director): self
    {
        $this->director = $director;

        return $this;
    }

    public function getYoutubeTrailer(): ?string
    {
        return str_replace("www.youtube.com/watch?v=", "www.youtube.com/embed/", $this->youtubeTrailer);
    }

    public function setYoutubeTrailer(?string $youtubeTrailer): self
    {
        $this->youtubeTrailer = $youtubeTrailer;

        return $this;
    }

    public function getAwards(): ?string
    {
        return $this->awards;
    }

    public function setAwards(?string $awards): self
    {
        $this->awards = $awards;

        return $this;
    }

    public function getYearStart(): ?int
    {
        return $this->yearStart;
    }

    public function setYearStart(?int $yearStart): self
    {
        $this->yearStart = $yearStart;

        return $this;
    }

    public function getYearEnd(): ?int
    {
        return $this->yearEnd;
    }

    public function setYearEnd(?int $yearEnd): self
    {
        $this->yearEnd = $yearEnd;

        return $this;
    }

    public function getExternalRatings(): Collection
    {
        return $this->externalRatings;
    }

    public function getRatings(): Collection
    {
        return $this->ratings;
    }

    /**
     * @return Collection<int, User>
     */
    public function getUser(): Collection
    {
        return $this->user;
    }

    public function addUser(User $user): self
    {
        if (!$this->user->contains($user)) {
            $this->user->add($user);
            $user->addSeries($this);
        }

        return $this;
    }

    public function removeUser(User $user): self
    {
        if ($this->user->removeElement($user)) {
            $user->removeSeries($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Actor>
     */
    public function getActor(): Collection
    {
        return $this->actor;
    }

    public function addActor(Actor $actor): self
    {
        if (!$this->actor->contains($actor)) {
            $this->actor->add($actor);
            $actor->addSeries($this);
        }

        return $this;
    }

    public function removeActor(Actor $actor): self
    {
        if ($this->actor->removeElement($actor)) {
            $actor->removeSeries($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Country>
     */
    public function getCountry(): Collection
    {
        return $this->country;
    }

    public function addCountry(Country $country): self
    {
        if (!$this->country->contains($country)) {
            $this->country->add($country);
            $country->addSeries($this);
        }

        return $this;
    }

    public function removeCountry(Country $country): self
    {
        if ($this->country->removeElement($country)) {
            $country->removeSeries($this);
        }

        return $this;
    }

    public function getPoster()
    {
        return $this->poster;
    }

    public function setPoster($poster): static
    {
        $this->poster = $poster;

        return $this;
    }

    /**
     * @return Collection<int, Season>
     */
    public function getSeasons(): Collection
    {
        return $this->seasons;
    }

    public function addSeason(Season $season): static
    {
        if (!$this->seasons->contains($season)) {
            $this->seasons->add($season);
            $season->setSeries($this);
        }

        return $this;
    }

    public function removeSeason(Season $season): static
    {
        if ($this->seasons->removeElement($season)) {
            // set the owning side to null (unless already changed)
            if ($season->getSeries() === $this) {
                $season->setSeries(null);
            }
        }

        return $this;
    }

    public function addExternalRating(ExternalRating $externalRating): static
    {
        if (!$this->externalRatings->contains($externalRating)) {
            $this->externalRatings->add($externalRating);
            $externalRating->setSeries($this);
        }

        return $this;
    }

    public function removeExternalRating(ExternalRating $externalRating): static
    {
        if ($this->externalRatings->removeElement($externalRating)) {
            // set the owning side to null (unless already changed)
            if ($externalRating->getSeries() === $this) {
                $externalRating->setSeries(null);
            }
        }

        return $this;
    }

    public function addRating(Rating $rating): static
    {
        if (!$this->ratings->contains($rating)) {
            $this->ratings->add($rating);
            $rating->setSeries($this);
        }

        return $this;
    }

    public function removeRating(Rating $rating): static
    {
        if ($this->ratings->removeElement($rating)) {
            // set the owning side to null (unless already changed)
            if ($rating->getSeries() === $this) {
                $rating->setSeries(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Genre>
     */
    public function getGenre(): Collection
    {
        return $this->genre;
    }

    public function addGenre(Genre $genre): static
    {
        if (!$this->genre->contains($genre)) {
            $this->genre->add($genre);
            $genre->addSeries($this);
        }

        return $this;
    }

    public function removeGenre(Genre $genre): static
    {
        if ($this->genre->removeElement($genre)) {
            $genre->removeSeries($this);
        }

        return $this;
    }
}

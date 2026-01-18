package models

import (
	"time"

	"github.com/google/uuid"
)

// Achievement definition
type Achievement struct {
	ID          string `json:"id" db:"id"`
	Name        string `json:"name" db:"name"`
	Description string `json:"description" db:"description"`
	Category    string `json:"category" db:"category"`
	IconURL     string `json:"icon_url" db:"icon_url"`
	Tier        int    `json:"tier" db:"tier"`
	Points      int    `json:"points" db:"points"`

	// Unlock criteria
	EventType string `json:"event_type" db:"event_type"`
	Threshold int    `json:"threshold" db:"threshold"`
	Condition string `json:"condition,omitempty" db:"condition"`

	// Rarity
	UnlockCount int     `json:"unlock_count" db:"unlock_count"`
	UnlockRate  float64 `json:"unlock_rate" db:"unlock_rate"`

	IsHidden  bool      `json:"is_hidden" db:"is_hidden"`
	CreatedAt time.Time `json:"created_at" db:"created_at"`

	// Computed/Transient
	Progress int64 `json:"progress,omitempty" db:"-"`
	Target   int64 `json:"target,omitempty" db:"-"`
}

// AchievementCategory groups achievements
type AchievementCategory string

const (
	CategoryFirstSteps   AchievementCategory = "First Steps"
	CategoryCombat       AchievementCategory = "Combat Novice"
	CategoryWeapon       AchievementCategory = "Weapon Specialist"
	CategoryTactical     AchievementCategory = "Tactical Excellence"
	CategoryHumiliation  AchievementCategory = "Humiliation"
	CategoryHallOfShame  AchievementCategory = "Hall of Shame"
	CategoryDedication   AchievementCategory = "Dedication & Milestones"
	CategoryHidden       AchievementCategory = "Hidden & Secret"
	CategoryTournament   AchievementCategory = "Tournament"
	CategoryCommunity    AchievementCategory = "Special & Community"
)

// AchievementDefinition holds the static data for an achievement
type AchievementDefinition struct {
	ID          string
	Name        string
	Description string
	Tier        int
	Category    AchievementCategory
	Target      int64 // Value needed to unlock (e.g., 100 kills)
	Metric      string // The metric name to check against (e.g., "kills", "headshots")
}

// PlayerAchievement tracks unlocked achievements
type PlayerAchievement struct {
	ID            uuid.UUID  `json:"id" db:"id"`
	PlayerGUID    string     `json:"player_guid" db:"player_guid"`
	AchievementID string     `json:"achievement_id" db:"achievement_id"`
	UnlockedAt    time.Time  `json:"unlocked_at" db:"unlocked_at"`
	MatchID       string     `json:"match_id,omitempty" db:"match_id"`
	NotifiedAt    *time.Time `json:"notified_at,omitempty" db:"notified_at"`
	
	// Join fields
	Achievement *Achievement `json:"achievement,omitempty" db:"-"`
}

var AllAchievements = []AchievementDefinition{
	// Tier 1: First Steps
	{
		ID:          "boot_camp",
		Name:        "Boot Camp Graduate",
		Description: "Complete your first match",
		Tier:        1,
		Category:    CategoryFirstSteps,
		Target:      1,
		Metric:      "matches",
	},
	{
		ID:          "first_blood",
		Name:        "First Blood",
		Description: "Get your first kill",
		Tier:        1,
		Category:    CategoryFirstSteps,
		Target:      1,
		Metric:      "kills",
	},
	{
		ID:          "centurion",
		Name:        "Centurion",
		Description: "Get 100 kills",
		Tier:        2,
		Category:    CategoryCombat,
		Target:      100,
		Metric:      "kills",
	},
	{
		ID:          "headhunter",
		Name:        "Headhunter",
		Description: "Get 100 headshots",
		Tier:        2,
		Category:    CategoryCombat,
		Target:      100,
		Metric:      "headshots",
	},
	{
		ID:          "marathon",
		Name:        "Marathon Runner",
		Description: "Travel 10km total",
		Tier:        2,
		Category:    CategoryCombat,
		Target:      10000, // assuming meters
		Metric:      "distance", // needs support in logic
	},
}
